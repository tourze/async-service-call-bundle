<?php

namespace Tourze\AsyncServiceCallBundle\Tests\Message;

use PHPUnit\Framework\TestCase;
use Tourze\AsyncServiceCallBundle\Message\ServiceCallMessage;

class ServiceCallMessageTest extends TestCase
{
    public function testServiceIdGetterAndSetter(): void
    {
        $message = new ServiceCallMessage();
        $message->setServiceId('test.service');
        $this->assertEquals('test.service', $message->getServiceId());
    }

    public function testMethodGetterAndSetter(): void
    {
        $message = new ServiceCallMessage();
        $message->setMethod('testMethod');
        $this->assertEquals('testMethod', $message->getMethod());
    }

    public function testParamsGetterAndSetter(): void
    {
        $message = new ServiceCallMessage();
        $params = ['param1' => 'value1', 'param2' => 123];
        $message->setParams($params);
        $this->assertEquals($params, $message->getParams());
    }

    public function testRetryCountGetterAndSetter(): void
    {
        $message = new ServiceCallMessage();
        $message->setRetryCount(5);
        $this->assertEquals(5, $message->getRetryCount());
    }

    public function testMaxRetryCountGetterAndSetter(): void
    {
        $message = new ServiceCallMessage();
        $message->setMaxRetryCount(10);
        $this->assertEquals(10, $message->getMaxRetryCount());
    }
}
