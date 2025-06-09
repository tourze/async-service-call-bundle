<?php

namespace Tourze\AsyncServiceCallBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Tourze\DoctrineHelper\EntityDetector;
use Tourze\DoctrineHelper\ReflectionHelper;

/**
 * 扩容默认的对象处理器，让他支持加载实体
 */
/** @phpstan-ignore class.extendsFinalByPhpDoc */
class ObjectNormalizer extends \Symfony\Component\Serializer\Normalizer\ObjectNormalizer
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    )
    {
        parent::__construct(propertyTypeExtractor: new ReflectionExtractor());
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            'object' => __CLASS__ === static::class,
        ];
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        // 所有Entity对象，我们都只返回一个id
        if (EntityDetector::isEntityClass($object::class)) {
            return [
                'id' => $object->getId(),
            ];
        }
        return parent::normalize($object, $format, $context);
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (EntityDetector::isEntityClass($type) && isset($data['id'])) {
            $id = $data['id'];
            $value = $this->entityManager->find($type, $id);
            $this->logger->debug('denormalize反序列化实体对象数据', [
                'id' => $id,
                'value' => $value,
            ]);
            return $value;
        }

        return parent::denormalize($data, $type, $format, $context);
    }

    protected function setAttributeValue(object $object, string $attribute, mixed $value, ?string $format = null, array $context = []): void
    {
        $property = ReflectionHelper::getReflectionProperty($object, $attribute);
        if (!!$value && $property && $property->getType() instanceof \ReflectionNamedType) {
            $className = $property->getType()->getName();
            // 如果是一个实体类，我们额外处理
            if (EntityDetector::isEntityClass($className) && !is_object($value)) {
                $id = $value['id'];
                $value = $this->entityManager->find($property->getType()->getName(), $id);
                $this->logger->debug('setAttributeValue反序列化实体对象数据', [
                    'id' => $id,
                    'value' => $value,
                ]);
            } else {
                // 普通的类是否需要处理？
            }
        }

        parent::setAttributeValue($object, $attribute, $value, $format, $context);
    }
}
