<?php

namespace Tourze\AsyncServiceCallBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\NullLogger;
use Tourze\AsyncServiceCallBundle\Exception\InvalidParameterException;
use Tourze\AsyncServiceCallBundle\Service\Serializer;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(Serializer::class)]
#[RunTestsInSeparateProcesses]
final class SerializerTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 无需额外的初始化逻辑
    }

    private function createSerializer(): Serializer
    {
        $logger = new NullLogger();

        // 使用反射创建实例以避免直接实例化
        $reflection = new \ReflectionClass(Serializer::class);

        return $reflection->newInstance($logger);
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
        $real = $this->createSerializer();
        $encoded = $real->encodeParams($params);

        // 验证编码格式正确
        foreach ($encoded as $value) {
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

        $real = $this->createSerializer();
        $real->encodeParams($params);
    }

    public function testSerializeWithDateTime(): void
    {
        $date = new \DateTime('2023-01-01 12:00:00');
        $serializer = $this->createSerializer();
        $encoded = $serializer->serialize($date, 'json');
        $this->assertJson($encoded);
        $this->assertStringContainsString('2023-01-01', $encoded);
    }

    public function testDeserializeWithDateTime(): void
    {
        $json = '"2023-01-01T12:00:00+00:00"';
        $serializer = $this->createSerializer();
        $decoded = $serializer->deserialize($json, \DateTime::class, 'json');
        $this->assertInstanceOf(\DateTime::class, $decoded);
        $this->assertEquals('2023-01-01 12:00:00', $decoded->format('Y-m-d H:i:s'));
    }

    public function testEncodeParams(): void
    {
        $params = [
            'string' => 'test',
            'int' => 123,
            'bool' => true,
            'array' => ['a', 'b'],
            'object' => new \DateTime('2023-01-01 12:00:00'),
        ];

        $real = $this->createSerializer();
        $encoded = $real->encodeParams($params);

        self::assertArrayHasKey('string', $encoded);
        self::assertEquals(['string', 'test'], $encoded['string']);

        self::assertArrayHasKey('int', $encoded);
        self::assertEquals(['integer', 123], $encoded['int']);

        self::assertArrayHasKey('bool', $encoded);
        self::assertEquals(['boolean', true], $encoded['bool']);

        self::assertArrayHasKey('array', $encoded);
        self::assertEquals(['array', ['a', 'b']], $encoded['array']);

        self::assertArrayHasKey('object', $encoded);
        self::assertEquals('object', $encoded['object'][0]);
        self::assertEquals('DateTime', $encoded['object'][1]);
        self::assertIsString($encoded['object'][2]);
    }

    public function testDecodeParams(): void
    {
        $encodedParams = [
            'string' => ['string', 'test'],
            'int' => ['integer', 123],
            'bool' => ['boolean', true],
            'array' => ['array', ['a', 'b']],
            'object' => ['object', 'DateTime', '"2023-01-01T12:00:00+00:00"'],
        ];

        $real = $this->createSerializer();
        $decoded = $real->decodeParams($encodedParams);

        self::assertEquals('test', $decoded['string']);
        self::assertEquals(123, $decoded['int']);
        self::assertTrue($decoded['bool']);
        self::assertEquals(['a', 'b'], $decoded['array']);
        self::assertInstanceOf(\DateTime::class, $decoded['object']);
        self::assertEquals('2023-01-01 12:00:00', $decoded['object']->format('Y-m-d H:i:s'));
    }
}
