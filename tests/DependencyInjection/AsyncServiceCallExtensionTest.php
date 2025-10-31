<?php

namespace Tourze\AsyncServiceCallBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\AsyncServiceCallBundle\DependencyInjection\AsyncServiceCallExtension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(AsyncServiceCallExtension::class)]
final class AsyncServiceCallExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private AsyncServiceCallExtension $extension;

    private ContainerBuilder $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = new AsyncServiceCallExtension();
        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.environment', 'test');
    }

    public function testLoadWithEmptyConfigs(): void
    {
        $this->extension->load([], $this->container);

        // 应该没有异常抛出，容器应该仍然可用
        $this->assertTrue(
            $this->container->hasDefinition('Tourze\AsyncServiceCallBundle\MessageHandler\ServiceCallHandler')
            || $this->container->hasAlias('Tourze\AsyncServiceCallBundle\MessageHandler\ServiceCallHandler')
        );
    }

    public function testLoadWithMultipleConfigArrays(): void
    {
        $configs = [
            [],
            ['some_key' => 'some_value'],
            [],
        ];

        $this->extension->load($configs, $this->container);

        // 应该没有异常抛出
        $this->assertTrue(
            $this->container->hasDefinition('Tourze\AsyncServiceCallBundle\MessageHandler\ServiceCallHandler')
            || $this->container->hasAlias('Tourze\AsyncServiceCallBundle\MessageHandler\ServiceCallHandler')
        );
    }

    public function testExtensionAlias(): void
    {
        // Symfony Extension 的默认别名是类名去掉 Extension 后缀并转换为下划线格式
        $this->assertEquals('async_service_call', $this->extension->getAlias());
    }

    public function testConfigurationDirectoryExists(): void
    {
        $configDir = __DIR__ . '/../../src/Resources/config';
        $this->assertDirectoryExists($configDir);
    }

    public function testServicesYamlFileExists(): void
    {
        $servicesFile = __DIR__ . '/../../src/Resources/config/services.yaml';
        $this->assertFileExists($servicesFile);
    }
}
