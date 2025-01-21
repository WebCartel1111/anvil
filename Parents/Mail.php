<?php

namespace App\Containers\Vendor\Anvil\Parents;

use Apiato\Core\Abstracts\Mails\Mail as ApiatoMail;
use App\Containers\Vendor\Anvil\Models\MailTemplate;
use App\Ship\Parents\Models\Model;
use App\Ship\Parents\Models\UserModel;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Arr;

abstract class Mail extends ApiatoMail
{
    use Queueable;

    protected string $templateName;
    protected MailTemplate $template;
    public array $properties = [];

    public function __construct()
    {
        $this->prepareProperties();
        $this->getTemplate();
    }

    abstract function variables(): array;

    public function action(): ?array
    {
        return null;
    }

    public function getTemplate(): MailTemplate
    {
        if(!isset($this->template)) {
            $template = MailTemplate::where('name', $this->templateName)->firstOrFail();
            $this->setTemplate($template);
        }

        return $this->template;
    }

    public function setTemplate(MailTemplate $template): void
    {
        $this->template = $template;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    private function prepareProperties(): void
    {
        // Create Class Reflection
        $reflection = new \ReflectionClass($this);

        // Get all properties, but this method returns this class properties as well
        $classProperties = $reflection->getProperties();

        // Filter out the abstract class properties
        $filteredProperties = [];

        foreach ($classProperties as $classProperty) {
            if ($classProperty->class !== get_class($this)) {
                continue;
            }

            $filteredProperties[$classProperty->name] = $classProperty->getType();
        }

        // Get available mail properties from models
        $properties = [];

        foreach ($filteredProperties as $key => $filteredProperty) {
            try {
                $propertyName = $filteredProperty->getName();

                $model = (new $propertyName);

                if ($model instanceof Model || $model instanceof UserModel) {
                    $properties[$key] = $model->getMailFields();
                }
            } catch (\Error $ex) {
                continue;
            }
        }

        // Make all attributes in the dot format ($model.$property)
        foreach ($properties as $key => $attributes) {
            foreach ($attributes as $attribute) {
                $this->properties[] = $key.'.'.$attribute;
            }
        }
    }

    protected function replaceVariables(string $text): string|null
    {
        $variablesDotted = Arr::dot($this->variables());

        // Protection to use only email variables
        $variables = Arr::only($variablesDotted, $this->getProperties());

        // Define a callback function to replace each match
        $replace_callback = function ($match) use ($variables) {
            $property = $match[1];
            if (isset($variables[$property])) {
                return $variables[$property];
            } else {
                return $match[0];
            }
        };

        return preg_replace_callback(
            '/{{\s*([\w.]+)\s*}}/',
            $replace_callback,
            $text
        );
    }

    protected function build(): Mail
    {
        $data = $this->template->toArray();

        $data['body']['text'] = $this->replaceVariables($data['body']['text']);
        $data['subject'] = $this->replaceVariables($data['subject']);

        return $this->view('anvil.mail')->with([
            'subject' => $data['subject'],
            'text' => $data['body']['text'] ?? '',
            'action' => $this->action(),
            'settings' => config('anvil.mail_settings'),
        ]);
    }

    public function shouldSend(): bool
    {
        return (bool) $this->template->active;
    }
}