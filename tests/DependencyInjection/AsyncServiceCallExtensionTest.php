<?php

namespace Tourze\AsyncServiceCallBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\AsyncServiceCallBundle\DependencyInjection\AsyncServiceCallExtension;

class AsyncServiceCallExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $extension = new AsyncServiceCallExtension();

        $extension->load([], $container);

        // 检查服务是否已通过自动配置加载
        self::assertTrue($container->hasDefinition('Tourze\AsyncServiceCallBundle\MessageHandler\ServiceCallHandler'));
        self::assertTrue($container->hasDefinition('Tourze\AsyncServiceCallBundle\Service\Serializer'));
    }
}