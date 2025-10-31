<?php

declare(strict_types=1);

namespace Tourze\AsyncServiceCallBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer as SymfonyObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectToPopulateTrait;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;
use Tourze\DoctrineHelper\EntityDetector;
use Tourze\DoctrineHelper\ReflectionHelper;

/**
 * 自定义对象处理器，支持加载实体
 */
class ObjectNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait;
    use ObjectToPopulateTrait;

    private SymfonyObjectNormalizer $decoratedNormalizer;

    private PropertyAccessorInterface $propertyAccessor;

    private PropertyTypeExtractorInterface $propertyTypeExtractor;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        ?ClassMetadataFactoryInterface $classMetadataFactory = null,
        ?NameConverterInterface $nameConverter = null,
        ?PropertyAccessorInterface $propertyAccessor = null,
        ?PropertyTypeExtractorInterface $propertyTypeExtractor = null,
    ) {
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
        $this->propertyTypeExtractor = $propertyTypeExtractor ?? new ReflectionExtractor();

        $this->decoratedNormalizer = new SymfonyObjectNormalizer(
            classMetadataFactory: $classMetadataFactory,
            nameConverter: $nameConverter,
            propertyAccessor: $this->propertyAccessor,
            propertyTypeExtractor: $this->propertyTypeExtractor,
        );
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            'object' => true,
        ];
    }

    /** @phpstan-ignore-next-line missingType.iterableValue */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return is_object($data);
    }

    /**
     * @return array<string, mixed>|string|int|float|bool|\ArrayObject<int|string, mixed>|null
     * @phpstan-ignore-next-line missingType.iterableValue
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        // 所有Entity对象，我们都只返回一个id
        if (EntityDetector::isEntityClass($object::class)) {
            return [
                'id' => $object->getId(),
            ];
        }

        return $this->decoratedNormalizer->normalize($object, $format, $context);
    }

    /** @phpstan-ignore-next-line missingType.iterableValue */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return class_exists($type);
    }

    /** @phpstan-ignore-next-line missingType.iterableValue */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        // 处理实体类的反序列化
        if (EntityDetector::isEntityClass($type) && is_array($data) && isset($data['id'])) {
            return $this->denormalizeEntity($type, $data['id']);
        }

        // 处理非实体类中的实体属性
        if (!EntityDetector::isEntityClass($type) && is_array($data)) {
            $data = $this->processEntityProperties($type, $data, $context);
        }

        return $this->decoratedNormalizer->denormalize($data, $type, $format, $context);
    }

    private function denormalizeEntity(string $type, mixed $id): mixed
    {
        /** @var class-string $type */
        $value = $this->entityManager->find($type, $id);
        $this->logger->debug('denormalize反序列化实体对象数据', [
            'id' => $id,
            'value' => $value,
        ]);

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function processEntityProperties(string $type, array $data, array $context): array
    {
        $object = $this->extractObjectToPopulate($type, $context) ?? new $type();

        foreach ($data as $attribute => $value) {
            if (!is_array($value) || !isset($value['id'])) {
                continue;
            }

            $entityValue = $this->tryLoadEntityProperty($object, $attribute, $value['id']);
            if (null !== $entityValue) {
                $data[$attribute] = $entityValue;
            }
        }

        return $data;
    }

    private function tryLoadEntityProperty(object $object, string $attribute, mixed $id): mixed
    {
        $property = ReflectionHelper::getReflectionProperty($object, $attribute);
        if (null === $property || !($property->getType() instanceof \ReflectionNamedType)) {
            return null;
        }

        $propertyType = $property->getType()->getName();
        if (!EntityDetector::isEntityClass($propertyType)) {
            return null;
        }

        /** @var class-string $propertyType */
        $entityValue = $this->entityManager->find($propertyType, $id);
        $this->logger->debug('setAttributeValue反序列化实体对象数据', [
            'id' => $id,
            'value' => $entityValue,
        ]);

        return $entityValue;
    }
}
