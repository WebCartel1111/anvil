<?php

namespace App\Containers\Vendor\Anvil\Tasks;

use Apiato\Core\Foundation\Facades\Apiato;
use App\Containers\Vendor\Anvil\Parents\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;

class MailTask
{
    private array $mailables = [];

    public function __construct()
    {
        $containersPaths = Apiato::getAllContainerPaths();

        foreach ($containersPaths as $containerPath) {
            try {
                $this->loadMails($containerPath);
            } catch (DirectoryNotFoundException|InvalidArgumentException $error) {
                continue;
            }
        }
    }

    public function find(string $name, ...$args): Mail|null
    {
        try {
            $data = $this->mailables;
            return new $data[$name](...$args);
        } catch (\Throwable $ex) {
            return null;
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function loadMails(string $containerPath): void
    {

        $cachedMailables = Cache::driver('file')->get('anvil.mailables');

        if($cachedMailables !== null) {
            $this->mailables = $cachedMailables;
            return;
        }

        $mailablePath = $containerPath . '/Mails';
        $mailableNamespace = $this->classPath($containerPath) . '\\Mails';

        // Load all files
        $files = File::allFiles($mailablePath);

        foreach ($files as $file) {

            if ($file->getRelativePath() !== '') {
                continue;
            }

            try {
                $className = str_replace('.php', '', $file->getFilename());
                $class = $mailableNamespace . '\\' . $className;

                $this->mailables[$className] = $class;
            } catch (\Error $error) {
                Log::error($error);
            }
        }

        Cache::driver('file')->add(
            'anvil.mailables',
            $this->mailables,
            now()->addDays(config('anvil.mail_settings.template_cache_ttl'))
        );
    }

    private function classPath(string $path): string
    {
        $path = str_replace(sprintf('%s/app', config('anvil.root_path')), 'App', $path);
        return str_replace('/', '\\', $path);
    }
}