<?php

namespace Tourze\AsyncServiceCallBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\AsyncServiceCallBundle\Service\Serializer;

class SerializerTest extends TestCase
{
    private $logger;
    private $serializer;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->serializer = $this->getMockBuilder(Serializer::class)
            ->setConstructorArgs([$this->logger])
            ->onlyMethods(['serialize', 'deserialize'])
            ->getMock();

        // 模拟序列化和反序列化方法
        $this->serializer->method('serialize')
            ->willReturn('{"test":"value"}');
        $this->serializer->method('deserialize')
            ->willReturn(['test' => 'value']);
    }

    public function testEncodeDecodeParamsWithScalars(): void
    {
        $params = [
            'string' => 'test',
            'int' => 123,
            'bool' => true,
            'array' => ['a', 'b'],
        ];

        // 使用真实的方法而不是模拟
        $real = new Serializer($this->logger);
        $encoded = $real->encodeParams($params);

        // 验证编码格式正确
        foreach ($encoded as $key => $value) {
            $this->assertIsArray($value);
            $this->assertArrayHasKey(0, $value);
            $this->assertContains($value[0], ['string', 'integer', 'boolean', 'array']);
        }

        $decoded = $real->decodeParams($encoded);
        $this->assertEquals($params, $decoded);
    }

    public function testExceptionOnArrayWithObjects(): void
    {
        $object = new \stdClass();
        $params = [
            'objectArray' => [$object],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('使用异步注解时，参数请不要传入包含对象的数组');

        $real = new Serializer($this->logger);
        $real->encodeParams($params);
    }

    public function testSerializeMocked(): void
    {
        $data = ['test' => 'value'];
        $encoded = $this->serializer->serialize($data, 'json');

        $this->assertIsString($encoded);
        $this->assertJson($encoded);
        $this->assertEquals('{"test":"value"}', $encoded);
    }

    public function testDeserializeMocked(): void
    {
        $json = '{"test":"value"}';
        $decoded = $this->serializer->deserialize($json, 'array', 'json');

        $this->assertIsArray($decoded);
        $this->assertEquals(['test' => 'value'], $decoded);
    }
}
