# Anvil Container for Apiato

[![Latest Version on Packagist](https://img.shields.io/packagist/v/webcartell/anvil
)](https://packagist.org/packages/webcartell/anvil)
[![Total Downloads](https://img.shields.io/packagist/dt/webcartell/anvil
)](https://packagist.org/packages/webcartell/anvil)
[![License](https://img.shields.io/github/license/webcartel1111/apiato-anvil
)](https://packagist.org/packages/webcartell/anvil)

A powerful toolkit for Apiato that provides advanced email templating, webhook management, and standardized API patterns.

## Features

- 📧 Dynamic Email Template System
- 🔗 Webhook Management
- 🎯 Standardized API Controllers
- 🛠️ Base Task Implementations
- 🔄 Event Dispatching
- 🚀 RPC Action Support

## Installation
```bash
php composer require webcartell/anvil
```

## Database Setup

Run the migrations to create the necessary tables:

```bash
php artisan migrate
```

This will create:
- `email_templates` table for managing email templates
- `webhooks` table for webhook configurations

## Core Components

### 1. Email System

The email system provides a flexible templating solution with:
- Dynamic variable replacement
- Template caching
- Active/inactive template states
- Customizable settings

```php
use App\Containers\Vendor\Anvil\Parents\Mail;

class WelcomeEmail extends Mail
{
    protected string $templateName = 'welcome_email';

    public function variables(): array
    {
        return [
            'user' => [
            'name' => 'John Doe'
            ]
        ];
    }
```


### 2. Webhook Management

Easily manage and trigger webhooks for your API events:
```php
use App\Containers\Vendor\Anvil\Models\Webhook;

// Register a webhook
Webhook::create([
    'event' => 'user.created',
    'url' => 'https://your-endpoint.com/webhook',
    'secret_key' => 'your-secret'
]);
```

### 3. AnvilController

A powerful base controller that provides:
- Automatic REST action handling
- Event dispatching
- Webhook triggering
- Transformer integration

```php
use App\Containers\Vendor\Anvil\Parents\AnvilController;

class UserController extends AnvilController
{
    public bool $dispatchesEvent = true;
    public bool $triggersWebhooks = true;
}
```

### 4. AnvilTask

Base task implementation with common database operations:
- Fetch all records
- Find by ID
- Create
- Update
- Delete


## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

Built for [Apiato](https://apiato.io) with ❤️