# 异步服务调用包

[![PHP Version](https://img.shields.io/badge/php-8.1%2B-blue.svg)](https://www.php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Build Status](https://img.shields.io/github/workflow/status/tourze/php-monorepo/CI/master)](https://github.com/tourze/php-monorepo/actions)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/php-monorepo/master)](https://codecov.io/gh/tourze/php-monorepo)

[English](README.md) | [中文](README.zh-CN.md)

基于 Symfony Messenger 的异步服务调用包，支持复杂对象参数序列化和重试机制。

## 目录

- [依赖要求](#依赖要求)
  - [包依赖](#包依赖)
- [安装](#安装)
- [快速开始](#快速开始)
  - [1. 注册 Bundle](#1-注册-bundle)
  - [2. 配置 Messenger](#2-配置-messenger)
  - [3. 创建服务调用消息](#3-创建服务调用消息)
- [功能特性](#功能特性)
  - [复杂对象参数序列化](#复杂对象参数序列化)
  - [重试机制](#重试机制)
  - [错误处理](#错误处理)
- [使用示例](#使用示例)
  - [基本用法](#基本用法)
  - [复杂对象参数](#复杂对象参数)
  - [枚举参数](#枚举参数)
- [高级用法](#高级用法)
  - [自定义服务调用处理器](#自定义服务调用处理器)
  - [高级序列化](#高级序列化)
  - [错误处理策略](#错误处理策略)
- [配置选项](#配置选项)
  - [服务配置](#服务配置)
  - [自定义序列化器](#自定义序列化器)
- [注意事项](#注意事项)
- [许可证](#许可证)

## 依赖要求

此包需要：

- PHP 8.1 或更高版本
- Symfony 6.4 或更高版本
- Doctrine ORM 3.0 或更高版本
- Symfony Messenger 组件

### 包依赖

- `tourze/async-contracts`: 提供异步操作的接口契约
- `tourze/doctrine-helper`: Doctrine 集成的帮助工具

## 安装

```bash
composer require tourze/async-service-call-bundle
```

## 快速开始

### 1. 注册 Bundle

在 `config/bundles.php` 中注册：

```php
return [
    // ...
    Tourze\AsyncServiceCallBundle\AsyncServiceCallBundle::class => ['all' => true],
];
```

### 2. 配置 Messenger

在 `config/packages/messenger.yaml` 中配置：

```yaml
framework:
    messenger:
        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
        routing:
            Tourze\AsyncServiceCallBundle\Message\ServiceCallMessage: async
```

### 3. 创建服务调用消息

```php
use Tourze\AsyncServiceCallBundle\Message\ServiceCallMessage;

$message = new ServiceCallMessage();
$message->setServiceId('my.service.id');
$message->setMethod('processData');
$message->setParams(['param1', 'param2']);
$message->setMaxRetryCount(3);
$message->setRetryCount(3);

// 发送到消息队列
$messageBus->dispatch($message);
```

## 功能特性

### 复杂对象参数序列化

包内置了高级序列化器，支持：
- 普通数据类型（string, int, float, bool, array）
- 对象（包括 Doctrine 实体）
- 枚举类型（BackedEnum）
- 日期时间对象

### 重试机制

- 支持设置最大重试次数
- 指数退避延迟（最大延迟 1 小时）
- 完整的错误日志记录

### 错误处理

- 记录详细的错误日志
- 支持异常重试
- 优雅降级处理

## 使用示例

### 基本用法

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

### 复杂对象参数

```php
use Tourze\AsyncServiceCallBundle\Message\ServiceCallMessage;

// 传递实体对象
$user = $entityManager->find(User::class, 1);
$message = new ServiceCallMessage();
$message->setServiceId('user.service');
$message->setMethod('updateProfile');
$message->setParams([$user, ['name' => 'New Name']]);

$this->messageBus->dispatch($message);
```

### 枚举参数

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

## 高级用法

### 自定义服务调用处理器

你可以扩展默认的处理器来添加自定义逻辑：

```php
use Tourze\AsyncServiceCallBundle\MessageHandler\ServiceCallHandler;
use Tourze\AsyncServiceCallBundle\Message\ServiceCallMessage;

class CustomServiceCallHandler extends ServiceCallHandler
{
    public function __invoke(ServiceCallMessage $message): void
    {
        // 自定义前置处理
        $this->logger->info('正在处理自定义服务调用');
        
        // 调用父类处理器
        parent::__invoke($message);
        
        // 自定义后置处理
        $this->logger->info('自定义服务调用完成');
    }
}
```

### 高级序列化

本包提供了一个自定义的 `ObjectNormalizer`，它实现了 `NormalizerInterface` 和 `DenormalizerInterface` 接口来处理实体序列化。它会在序列化时自动将实体转换为其 ID，并在反序列化时重新加载它们。

对于复杂的自定义对象，你可以创建自己的规范化器：

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
        
        throw new \InvalidArgumentException('不支持的对象类型');
    }
    
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof MyCustomObject;
    }
    
    // 实现反规范化方法...
}
```

### 错误处理策略

为特定服务配置自定义错误处理：

```php
use Tourze\AsyncServiceCallBundle\Exception\InvalidParameterException;

try {
    $message = new ServiceCallMessage();
    $message->setParams(['invalid' => new \stdClass()]);
    $messageBus->dispatch($message);
} catch (InvalidParameterException $e) {
    // 处理序列化错误
    $logger->error('序列化失败', ['error' => $e->getMessage()]);
}
```

## 配置选项

### 服务配置

包会自动注册以下服务：
- `Tourze\AsyncServiceCallBundle\MessageHandler\ServiceCallHandler`
- `Tourze\AsyncServiceCallBundle\Service\Serializer`

### 自定义序列化器

如果需要自定义序列化行为，可以扩展 `Serializer` 类：

```php
use Tourze\AsyncServiceCallBundle\Service\Serializer;

class MyCustomSerializer extends Serializer
{
    // 自定义序列化逻辑
}
```

## 注意事项

1. **参数限制**：不支持包含对象的数组参数
2. **依赖注入**：确保目标服务已正确注册到容器中
3. **错误处理**：建议配置适当的日志级别以便调试
4. **性能考虑**：复杂对象序列化可能影响性能

## 许可证

MIT License
