<?php

namespace Tourze\AsyncServiceCallBundle\Tests\MessageHandler;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Tourze\AsyncServiceCallBundle\MessageHandler\ServiceCallHandler;
use Tourze\AsyncServiceCallBundle\Service\Serializer;

class ServiceCallHandlerTest extends TestCase
{
    private ServiceCallHandler $handler;

    protected function setUp(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $serializer = $this->createMock(Serializer::class);
        $logger = $this->createMock(LoggerInterface::class);
        $messageBus = $this->createMock(MessageBusInterface::class);

        $this->handler = new ServiceCallHandler(
            $container,
            $serializer,
            $logger,
            $messageBus
        );
    }

    public function testHandlerCanBeInstantiated(): void
    {
        $this->assertInstanceOf(ServiceCallHandler::class, $this->handler);
    }
}
