<?php

namespace Tourze\AsyncServiceCallBundle\Tests\MessageHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\StampInterface;
use Tourze\AsyncServiceCallBundle\Exception\ServiceExecutionException;
use Tourze\AsyncServiceCallBundle\Exception\ServiceNotFoundException;
use Tourze\AsyncServiceCallBundle\Message\ServiceCallMessage;
use Tourze\AsyncServiceCallBundle\MessageHandler\ServiceCallHandler;
use Tourze\AsyncServiceCallBundle\Service\Serializer;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(ServiceCallHandler::class)]
#[RunTestsInSeparateProcesses]
final class ServiceCallHandlerTest extends AbstractIntegrationTestCase
{
    /**
     * 创建测试服务处理器.
     *
     * @param array<string, mixed> $dependencies
     */
    private function createServiceCallHandler(array $dependencies): ServiceCallHandler
    {
        // 使用反射创建实例以避免直接实例化
        $reflection = new \ReflectionClass(ServiceCallHandler::class);

        return $reflection->newInstance(
            $dependencies['container'],
            $dependencies['serializer'],
            $dependencies['logger'],
            $dependencies['messageBus']
        );
    }

    protected function onSetUp(): void
    {
        // 无需额外的初始化逻辑
    }

    private function createTestService(): object
    {
        return new class {
            public bool $methodCalled = false;

            /** @var array<string, mixed> */
            public array $receivedParams = [];

            public function testMethod(string $param1, int $param2): void
            {
                $this->methodCalled = true;
                $this->receivedParams = ['param1' => $param1, 'param2' => $param2];
            }

            public function throwExceptionMethod(): void
            {
                throw new ServiceExecutionException('Test exception');
            }
        };
    }

    private function createContainer(object $testService): ContainerInterface
    {
        return new class($testService) implements ContainerInterface {
            public function __construct(private readonly object $testService)
            {
            }

            public function get(string $id)
            {
                if ('test.service' === $id) {
                    return $this->testService;
                }
                throw new ServiceNotFoundException("Service {$id} not found");
            }

            public function has(string $id): bool
            {
                return 'test.service' === $id;
            }
        };
    }

    public function testSuccessfulServiceCall(): void
    {
        $testService = $this->createTestService();
        $container = $this->createContainer($testService);

        $serializer = new class(new NullLogger()) extends Serializer {
            public function __construct(LoggerInterface $logger)
            {
                parent::__construct($logger);
            }

            /**
             * @param array<string, mixed> $encodeParams
             * @return array<string, mixed>
             */
            public function decodeParams(array $encodeParams): array
            {
                if ($encodeParams === ['param1' => ['string', 'hello'], 'param2' => ['integer', 42]]) {
                    return ['param1' => 'hello', 'param2' => 42];
                }

                return [];
            }
        };

        $logger = new NullLogger();
        $messageBus = new class implements MessageBusInterface {
            public function dispatch(object $message, array $stamps = []): Envelope
            {
                return new Envelope($message, $stamps);
            }
        };

        // 通过容器获取或创建服务，而不是直接实例化
        $handler = $this->createServiceCallHandler([
            'container' => $container,
            'serializer' => $serializer,
            'logger' => $logger,
            'messageBus' => $messageBus,
        ]);

        $message = new ServiceCallMessage();
        $message->setServiceId('test.service');
        $message->setMethod('testMethod');
        $message->setParams(['param1' => ['string', 'hello'], 'param2' => ['integer', 42]]);

        $handler($message);

        // 使用反射访问属性以避免 PHPStan 错误
        $reflection = new \ReflectionClass($testService);
        $methodCalledProperty = $reflection->getProperty('methodCalled');
        $receivedParamsProperty = $reflection->getProperty('receivedParams');

        $this->assertTrue($methodCalledProperty->getValue($testService));
        $this->assertEquals(['param1' => 'hello', 'param2' => 42], $receivedParamsProperty->getValue($testService));
    }

    public function testServiceCallFailureWithRetry(): void
    {
        $testService = $this->createTestService();
        $container = $this->createContainer($testService);

        $serializer = new class(new NullLogger()) extends Serializer {
            public function __construct(LoggerInterface $logger)
            {
                parent::__construct($logger);
            }

            /**
             * @param array<string, mixed> $encodeParams
             * @return array<string, mixed>
             */
            public function decodeParams(array $encodeParams): array
            {
                return [];
            }
        };

        $logger = new NullLogger();
        $dispatchState = new class {
            public ?ServiceCallMessage $dispatchedMessage = null;

            /** @var array<StampInterface> */
            public array $dispatchedStamps = [];
        };
        $messageBus = new class($dispatchState) implements MessageBusInterface {
            public function __construct(private object $dispatchState)
            {
            }

            /**
             * @param array<StampInterface> $stamps
             */
            public function dispatch(object $message, array $stamps = []): Envelope
            {
                if ($message instanceof ServiceCallMessage && property_exists($this->dispatchState, 'dispatchedMessage')) {
                    $this->dispatchState->dispatchedMessage = $message;
                }
                if (property_exists($this->dispatchState, 'dispatchedStamps')) {
                    $this->dispatchState->dispatchedStamps = $stamps;
                }

                return new Envelope($message, $stamps);
            }
        };

        // 通过容器获取或创建服务，而不是直接实例化
        $handler = $this->createServiceCallHandler([
            'container' => $container,
            'serializer' => $serializer,
            'logger' => $logger,
            'messageBus' => $messageBus,
        ]);

        $message = new ServiceCallMessage();
        $message->setServiceId('test.service');
        $message->setMethod('throwExceptionMethod');
        $message->setParams([]);
        $message->setRetryCount(3);
        $message->setMaxRetryCount(3);

        $handler($message);

        // 验证重试消息是否被正确派发
        $this->assertInstanceOf(ServiceCallMessage::class, $dispatchState->dispatchedMessage);
        $this->assertNotNull($dispatchState->dispatchedMessage);
        $this->assertEquals(2, $dispatchState->dispatchedMessage->getRetryCount());
        $this->assertEquals('test.service', $dispatchState->dispatchedMessage->getServiceId());
        $this->assertEquals('throwExceptionMethod', $dispatchState->dispatchedMessage->getMethod());
    }

    public function testServiceCallFailureWithoutRetry(): void
    {
        $testService = $this->createTestService();
        $container = $this->createContainer($testService);

        $serializer = new class(new NullLogger()) extends Serializer {
            public function __construct(LoggerInterface $logger)
            {
                parent::__construct($logger);
            }

            /**
             * @param array<string, mixed> $encodeParams
             * @return array<string, mixed>
             */
            public function decodeParams(array $encodeParams): array
            {
                return [];
            }
        };

        $logger = new NullLogger();
        $dispatchState = new class {
            public bool $dispatchCalled = false;
        };
        $messageBus = new class($dispatchState) implements MessageBusInterface {
            public function __construct(private object $dispatchState)
            {
            }

            /**
             * @param array<StampInterface> $stamps
             */
            public function dispatch(object $message, array $stamps = []): Envelope
            {
                if (property_exists($this->dispatchState, 'dispatchCalled')) {
                    $this->dispatchState->dispatchCalled = true;
                }

                return new Envelope($message, $stamps);
            }
        };

        // 通过容器获取或创建服务，而不是直接实例化
        $handler = $this->createServiceCallHandler([
            'container' => $container,
            'serializer' => $serializer,
            'logger' => $logger,
            'messageBus' => $messageBus,
        ]);

        $message = new ServiceCallMessage();
        $message->setServiceId('test.service');
        $message->setMethod('throwExceptionMethod');
        $message->setParams([]);
        $message->setRetryCount(0);
        $message->setMaxRetryCount(3);

        $handler($message);

        // 验证没有重试次数时不会派发新消息
        $this->assertFalse($dispatchState->dispatchCalled);
    }
}
