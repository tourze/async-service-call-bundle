<?php

namespace Tourze\AsyncServiceCallBundle\Tests\Model;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\AsyncServiceCallBundle\Model\ObjectNormalizer;

class ObjectNormalizerTest extends TestCase
{
    private ObjectNormalizer $normalizer;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->normalizer = new ObjectNormalizer($this->entityManager, $this->logger);
    }

    public function testGetSupportedTypes(): void
    {
        $supportedTypes = $this->normalizer->getSupportedTypes(null);
        self::assertArrayHasKey('object', $supportedTypes);
        self::assertTrue($supportedTypes['object']);
    }

    public function testNormalizeNonEntity(): void
    {
        $object = new \stdClass();
        $object->foo = 'bar';

        $result = $this->normalizer->normalize($object);
        self::assertArrayHasKey('foo', $result);
        self::assertEquals('bar', $result['foo']);
    }
}