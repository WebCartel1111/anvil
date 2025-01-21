<?php

namespace App\Containers\Vendor\Anvil\Parents;

use Apiato\Core\Exceptions\InvalidTransformerException;
use App\Containers\Vendor\Anvil\Models\Webhook;
use App\Ship\Parents\Controllers\ApiController;
use Spatie\WebhookServer\WebhookCall;

class AnvilController extends ApiController
{
    protected string $containerName;

    protected string $vendorName;

    private array $mappings = [
        'methods' => [
            'index' => 'GetAll%s',
            'find' => 'Find%sById',
            'store' => 'Create%s',
            'update' => 'Update%s',
            'delete' => 'Delete%s',
        ],
        'events' => [
            'index' => 'listed',
            'find' => 'fetched',
            'store' => 'created',
            'update' => 'updated',
            'delete' => 'deleted'
        ]
    ];

    public bool $dispatchesEvent = false;
    
    public bool $triggersWebhooks = false;

    public function __construct()
    {
        $this->getContainerData();
    }

    /**
     * @throws InvalidTransformerException
     */
    public function __call($method, $parameters)
    {
        if(strpos($method, 'rpc')) {
            return $this->handleRpcAction($method);
        }

        return $this->handleRestActions($method);
    }

    /**
     * @throws InvalidTransformerException
     */
    private function handleRestActions(string $method): \Illuminate\Http\JsonResponse
    {
        $requestPath = sprintf(
            '\\App\\Containers\\%s\\%s\\UI\\API\\Requests\\%s',
            $this->vendorName,
            $this->containerName,
            $this->mapAction($method, 'methods', 'request')
        );

        $actionPath = sprintf(
            '\\App\\Containers\\%s\\%s\\Actions\\%s',
            $this->vendorName,
            $this->containerName,
            $this->mapAction($method, 'methods', 'action')
        );

        $request = app($requestPath);
        $action = app($actionPath);

        $data = $action->run($request);

        $this->handleEventDispatch($data);
        $this->handleWebhookDispatch($method, $data);

        $response = $this->handleTransformers($data);

        return $this->json($response);
    }

    private function handleRpcAction(string $method): mixed
    {
        $actionName = substr($method, 3);
        $request = app('\\App\\Containers\\' . $this->vendorName . '\\' . $this->containerName . '\\UI\\API\\Requests\\RPC\\' . $actionName . 'Request');
        $action = app('App\\Containers\\' . $this->vendorName . '\\' . $this->containerName . '\\Actions\\RPC\\' . $actionName . 'Action');

        return $action->run($request);
    }

    protected function handleEventDispatch($data): void
    {
        $event = sprintf(
            '\\App\\Containers\\%s\\%s\\Events\\%s',
            $this->vendorName,
            $this->containerName,
            $this->containerName . 'Event'
        );

        if($this->dispatchesEvent && class_exists($event)) {
            $event::dispatch($data);
        }
    }

    protected function handleWebhookDispatch(string $method, $payload): void
    {
        if(!$this->triggersWebhooks) {
            return;
        }

        $webhook = Webhook::where('event', $this->mapAction($method, 'events'))->first();

        if(!isset($webhook)) {
            return;
        }

        $webhookCall = WebhookCall::create()->url($webhook->url)->payload($payload);

        if(isset($webhook->secret)) {
            $webhookCall->useSecret($webhook->secret);
        }

        $webhookCall->dispatch();
    }

    /**
     * @throws InvalidTransformerException
     */
    protected function handleTransformers($data): ?array
    {
        $transformerPath = sprintf(
            '\\App\\Containers\\%s\\%s\\UI\\API\\Transformers\\%s',
            $this->vendorName,
            $this->containerName,
            $this->containerName . 'Transformer'
        );

        if(class_exists($transformerPath)) {
            return $this->transform($data, new $transformerPath);
        }

        return $data;
    }

    private function getContainerData(): void
    {
        $calledClass = explode('\\', get_called_class());

        $this->containerName = $calledClass[3];
        $this->vendorName = $calledClass[2];
    }

    private function mapAction(string $method, string $type, string $object = null): string
    {
        return sprintf($this->mappings[$type][$method], $this->containerName) . $object ? ucfirst($object) : '';
    }
}