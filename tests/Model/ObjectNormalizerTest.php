<?php

namespace Tourze\AsyncServiceCallBundle\Tests\Model;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\Cache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\FilterCollection;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Tourze\AsyncServiceCallBundle\Model\ObjectNormalizer;

/**
 * @internal
 */
#[CoversClass(ObjectNormalizer::class)]
final class ObjectNormalizerTest extends TestCase
{
    private ObjectNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        // 创建一个空的EntityManager实现，只实现最基础的方法
        $entityManager = $this->createStubEntityManager();
        $logger = new NullLogger();

        // 使用反射创建实例以避免直接实例化
        $reflection = new \ReflectionClass(ObjectNormalizer::class);
        $this->normalizer = $reflection->newInstance($entityManager, $logger);
    }

    /**
     * 创建一个最小的EntityManager实现用于测试
     */
    private function createStubEntityManager(): EntityManagerInterface
    {
        /** @phpstan-ignore-next-line symplify.complexAnonymousClass */
        return new class implements EntityManagerInterface {
            public function find(string $className, mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object
            {
                return null;
            }

            public function persist(object $entity): void
            {
                // 空实现
            }

            public function remove(object $entity): void
            {
                // 空实现
            }

            public function merge(object $entity): object
            {
                return $entity;
            }

            public function clear(?string $entityName = null): void
            {
                // 空实现
            }

            public function detach(object $entity): void
            {
                // 空实现
            }

            public function refresh(object $entity, LockMode|int|null $lockMode = null): void
            {
                // 空实现
            }

            public function flush(): void
            {
                // 空实现
            }

            public function getRepository(string $entityName): EntityRepository
            {
                throw new \RuntimeException('Not implemented in test stub');
            }

            public function getClassMetadata(string $className): ClassMetadata
            {
                throw new \RuntimeException('Not implemented in test stub');
            }

            /** @phpstan-ignore method.childReturnType */
            public function getMetadataFactory(): ClassMetadataFactory
            {
                throw new \RuntimeException('Not implemented in test stub');
            }

            public function initializeObject(object $obj): void
            {
                // 空实现
            }

            public function contains(object $entity): bool
            {
                return false;
            }

            public function getCache(): ?Cache
            {
                return null;
            }

            public function getConnection(): Connection
            {
                throw new \RuntimeException('Not implemented in test stub');
            }

            public function getExpressionBuilder(): Expr
            {
                throw new \RuntimeException('Not implemented in test stub');
            }

            public function beginTransaction(): void
            {
                // 空实现
            }

            /**
             * @param callable(static): mixed $func
             */
            public function transactional(callable $func): mixed
            {
                return $func($this);
            }

            public function commit(): void
            {
                // 空实现
            }

            public function rollback(): void
            {
                // 空实现
            }

            public function createQuery(string $dql = ''): Query
            {
                throw new \RuntimeException('Not implemented in test stub');
            }

            public function createNamedQuery(string $name): Query
            {
                throw new \RuntimeException('Not implemented in test stub');
            }

            public function createNativeQuery(string $sql, ResultSetMapping $rsm): NativeQuery
            {
                throw new \RuntimeException('Not implemented in test stub');
            }

            public function createNamedNativeQuery(string $name): NativeQuery
            {
                throw new \RuntimeException('Not implemented in test stub');
            }

            public function createQueryBuilder(): QueryBuilder
            {
                throw new \RuntimeException('Not implemented in test stub');
            }

            public function getReference(string $entityName, mixed $id): ?object
            {
                return null;
            }

            public function getPartialReference(string $entityName, mixed $identifier): null
            {
                return null;
            }

            public function close(): void
            {
                // 空实现
            }

            public function copy(object $entity, bool $deep = false): object
            {
                return $entity;
            }

            public function lock(object $entity, LockMode|int $lockMode, \DateTimeInterface|int|null $lockVersion = null): void
            {
                // 空实现
            }

            public function getEventManager(): EventManager
            {
                throw new \RuntimeException('Not implemented in test stub');
            }

            public function getConfiguration(): Configuration
            {
                throw new \RuntimeException('Not implemented in test stub');
            }

            public function isOpen(): bool
            {
                return true;
            }

            public function getUnitOfWork(): UnitOfWork
            {
                throw new \RuntimeException('Not implemented in test stub');
            }

            public function getHydrator(int|string $hydrationMode): AbstractHydrator
            {
                throw new \RuntimeException('Not implemented in test stub');
            }

            public function newHydrator(int|string $hydrationMode): AbstractHydrator
            {
                throw new \RuntimeException('Not implemented in test stub');
            }

            public function getProxyFactory(): ProxyFactory
            {
                throw new \RuntimeException('Not implemented in test stub');
            }

            public function getFilters(): FilterCollection
            {
                throw new \RuntimeException('Not implemented in test stub');
            }

            public function isFiltersStateClean(): bool
            {
                return true;
            }

            public function hasFilters(): bool
            {
                return false;
            }

            public function isUninitializedObject(mixed $value): bool
            {
                return false;
            }

            /**
             * @param callable(EntityManagerInterface): mixed $func
             */
            public function wrapInTransaction(callable $func): mixed
            {
                return $func($this);
            }
        };
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
        self::assertIsArray($result);
        self::assertArrayHasKey('foo', $result);
        self::assertEquals('bar', $result['foo']);
    }

    public function testSupportsNormalization(): void
    {
        $object = new \stdClass();
        $string = 'test';
        $array = ['test'];

        self::assertTrue($this->normalizer->supportsNormalization($object));
        self::assertFalse($this->normalizer->supportsNormalization($string));
        self::assertFalse($this->normalizer->supportsNormalization($array));
    }

    public function testSupportsDenormalization(): void
    {
        self::assertTrue($this->normalizer->supportsDenormalization([], \stdClass::class));
        self::assertTrue($this->normalizer->supportsDenormalization([], \DateTime::class));
        self::assertFalse($this->normalizer->supportsDenormalization([], 'NonExistentClass'));
    }

    public function testDenormalize(): void
    {
        // 测试 denormalize 方法可以正常调用，返回对象实例
        $data = ['foo' => 'bar'];
        $result = $this->normalizer->denormalize($data, \stdClass::class);

        // 只测试返回类型，不过度依赖内部实现细节
        self::assertInstanceOf(\stdClass::class, $result);
    }
}
