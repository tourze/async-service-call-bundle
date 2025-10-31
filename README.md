# Async Service Call Bundle

[![PHP Version](https://img.shields.io/badge/php-8.1%2B-blue.svg)](https://www.php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Build Status](https://img.shields.io/github/workflow/status/tourze/php-monorepo/CI/master)](https://github.com/tourze/php-monorepo/actions)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/php-monorepo/master)](https://codecov.io/gh/tourze/php-monorepo)

[English](README.md) | [中文](README.zh-CN.md)

An asynchronous service call bundle built on Symfony Messenger, supporting complex object parameter 
serialization and retry mechanisms.

## Table of Contents

- [Dependencies](#dependencies)
  - [Package Dependencies](#package-dependencies)
- [Installation](#installation)
- [Quick Start](#quick-start)
  - [1. Register Bundle](#1-register-bundle)
  - [2. Configure Messenger](#2-configure-messenger)
  - [3. Create Service Call Message](#3-create-service-call-message)
- [Features](#features)
  - [Complex Object Parameter Serialization](#complex-object-parameter-serialization)
  - [Retry Mechanism](#retry-mechanism)
  - [Error Handling](#error-handling)
- [Usage Examples](#usage-examples)
  - [Basic Usage](#basic-usage)
  - [Complex Object Parameters](#complex-object-parameters)
  - [Enum Parameters](#enum-parameters)
- [Advanced Usage](#advanced-usage)
  - [Custom Service Call Handler](#custom-service-call-handler)
  - [Advanced Serialization](#advanced-serialization)
  - [Error Handling Strategies](#error-handling-strategies)
- [Configuration Options](#configuration-options)
  - [Service Configuration](#service-configuration)
  - [Custom Serializer](#custom-serializer)
- [Notes](#notes)
- [License](#license)

## Dependencies

This package requires:

- PHP 8.1 or higher
- Symfony 6.4 or higher
- Doctrine ORM 3.0 or higher
- Symfony Messenger component

### Package Dependencies

- `tourze/async-contracts`: Provides interface contracts for async operations
- `tourze/doctrine-helper`: Helper utilities for Doctrine integration

## Installation

```bash
composer require tourze/async-service-call-bundle
```

## Quick Start

### 1. Register Bundle

Register in `config/bundles.php`:

```php
return [
    // ...
    Tourze\AsyncServiceCallBundle\AsyncServiceCallBundle::class => ['all' => true],
];
```

### 2. Configure Messenger

Configure in `config/packages/messenger.yaml`:

```yaml
framework:
    messenger:
        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
        routing:
            Tourze\AsyncServiceCallBundle\Message\ServiceCallMessage: async
```

### 3. Create Service Call Message

```php
use Tourze\AsyncServiceCallBundle\Message\ServiceCallMessage;

$message = new ServiceCallMessage();
$message->setServiceId('my.service.id');
$message->setMethod('processData');
$message->setParams(['param1', 'param2']);
$message->setMaxRetryCount(3);
$message->setRetryCount(3);

// Dispatch to message queue
$messageBus->dispatch($message);
```

## Features

### Complex Object Parameter Serialization

The bundle includes an advanced serializer that supports:
- Basic data types (string, int, float, bool, array)
- Objects (including Doctrine entities)
- Enum types (BackedEnum)
- DateTime objects

### Retry Mechanism

- Support for setting maximum retry count
- Exponential backoff delay (maximum 1 hour)
- Complete error logging

### Error Handling

- Detailed error logging
- Exception retry support
- Graceful degradation

## Usage Examples

### Basic Usage

```php
use Tourze\AsyncServiceCallBundle\Message\ServiceCallMessage;
use Symfony\Component\Messenger\MessageBusInterface;

class MyController
{
    public function __construct(
        private MessageBusInterface $messageBus
    ) {}

    public function sendAsyncCall(): void
    {
        $message = new ServiceCallMessage();
        $message->setServiceId('email.service');
        $message->setMethod('sendEmail');
        $message->setParams(['user@example.com', 'Subject', 'Body']);
        $message->setMaxRetryCount(5);
        $message->setRetryCount(5);

        $this->messageBus->dispatch($message);
    }
}
```

### Complex Object Parameters

```php
use Tourze\AsyncServiceCallBundle\Message\ServiceCallMessage;

// Pass entity objects
$user = $entityManager->find(User::class, 1);
$message = new ServiceCallMessage();
$message->setServiceId('user.service');
$message->setMethod('updateProfile');
$message->setParams([$user, ['name' => 'New Name']]);

$this->messageBus->dispatch($message);
```

### Enum Parameters

```php
enum Status: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}

$message = new ServiceCallMessage();
$message->setServiceId('status.service');
$message->setMethod('updateStatus');
$message->setParams([Status::ACTIVE]);

$this->messageBus->dispatch($message);
```

## Advanced Usage

### Custom Service Call Handler

You can extend the default handler to add custom logic:

```php
use Tourze\AsyncServiceCallBundle\MessageHandler\ServiceCallHandler;
use Tourze\AsyncServiceCallBundle\Message\ServiceCallMessage;

class CustomServiceCallHandler extends ServiceCallHandler
{
    public function __invoke(ServiceCallMessage $message): void
    {
        // Custom pre-processing
        $this->logger->info('Processing custom service call');
        
        // Call parent handler
        parent::__invoke($message);
        
        // Custom post-processing
        $this->logger->info('Custom service call completed');
    }
}
```

### Advanced Serialization

The bundle provides a custom `ObjectNormalizer` that implements `NormalizerInterface` and `DenormalizerInterface` to handle entity serialization. It automatically converts entities to their IDs during serialization and loads them back during deserialization.

For complex custom objects, you can create your own normalizers:

```php
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class CustomObjectNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        if ($object instanceof MyCustomObject) {
            return [
                'id' => $object->getId(),
                'data' => $object->serialize(),
            ];
        }
        
        throw new \InvalidArgumentException('Unsupported object type');
    }
    
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof MyCustomObject;
    }
    
    // Implement denormalization methods...
}
```

### Error Handling Strategies

Configure custom error handling for specific services:

```php
use Tourze\AsyncServiceCallBundle\Exception\InvalidParameterException;

try {
    $message = new ServiceCallMessage();
    $message->setParams(['invalid' => new \stdClass()]);
    $messageBus->dispatch($message);
} catch (InvalidParameterException $e) {
    // Handle serialization errors
    $logger->error('Serialization failed', ['error' => $e->getMessage()]);
}
```

## Configuration Options

### Service Configuration

The bundle automatically registers the following services:
- `Tourze\AsyncServiceCallBundle\MessageHandler\ServiceCallHandler`
- `Tourze\AsyncServiceCallBundle\Service\Serializer`

### Custom Serializer

If you need custom serialization behavior, you can extend the `Serializer` class:

```php
use Tourze\AsyncServiceCallBundle\Service\Serializer;

class MyCustomSerializer extends Serializer
{
    // Custom serialization logic
}
```

## Notes

1. **Parameter Limitations**: Does not support array parameters containing objects
2. **Dependency Injection**: Ensure target services are properly registered in the container
3. **Error Handling**: Configure appropriate log levels for debugging
4. **Performance Considerations**: Complex object serialization may impact performance

## License

MIT License