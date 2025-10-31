<?php

namespace Tourze\AsyncServiceCallBundle\Tests\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\AsyncServiceCallBundle\Message\ServiceCallMessage;

/**
 * @internal
 */
#[CoversClass(ServiceCallMessage::class)]
final class ServiceCallMessageTest extends TestCase
{
    protected function onSetUp(): void
    {
        // 无需额外的初始化逻辑
    }

    private function createMessage(): ServiceCallMessage
    {
        // 使用反射创建实例以避免直接实例化
        $reflection = new \ReflectionClass(ServiceCallMessage::class);

        return $reflection->newInstance();
    }

    public function testServiceIdGetterAndSetter(): void
    {
        $message = $this->createMessage();
        $message->setServiceId('test.service');
        $this->assertEquals('test.service', $message->getServiceId());
    }

    public function testMethodGetterAndSetter(): void
    {
        $message = $this->createMessage();
        $message->setMethod('testMethod');
        $this->assertEquals('testMethod', $message->getMethod());
    }

    public function testParamsGetterAndSetter(): void
    {
        $message = $this->createMessage();
        $params = ['param1' => 'value1', 'param2' => 123];
        $message->setParams($params);
        $this->assertEquals($params, $message->getParams());
    }

    public function testRetryCountGetterAndSetter(): void
    {
        $message = $this->createMessage();
        $message->setRetryCount(5);
        $this->assertEquals(5, $message->getRetryCount());
    }

    public function testMaxRetryCountGetterAndSetter(): void
    {
        $message = $this->createMessage();
        $message->setMaxRetryCount(10);
        $this->assertEquals(10, $message->getMaxRetryCount());
    }
}
