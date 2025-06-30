<?php

namespace Tourze\AsyncServiceCallBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\AsyncServiceCallBundle\Exception\InvalidParameterException;
use Tourze\AsyncServiceCallBundle\Service\Serializer;

class SerializerTest extends TestCase
{
    private $logger;
    private $serializer;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->serializer = new Serializer($this->logger);
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

        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('使用异步注解时，参数请不要传入包含对象的数组');

        $real = new Serializer($this->logger);
        $real->encodeParams($params);
    }

    public function testSerializeWithDateTime(): void
    {
        $date = new \DateTime('2023-01-01 12:00:00');
        $encoded = $this->serializer->serialize($date, 'json');
        $this->assertJson($encoded);
        $this->assertStringContainsString('2023-01-01', $encoded);
    }

    public function testDeserializeWithDateTime(): void
    {
        $json = '"2023-01-01T12:00:00+00:00"';
        $decoded = $this->serializer->deserialize($json, \DateTime::class, 'json');
        $this->assertInstanceOf(\DateTime::class, $decoded);
        $this->assertEquals('2023-01-01 12:00:00', $decoded->format('Y-m-d H:i:s'));
    }
}
