<?php

declare(strict_types=1);

namespace Tourze\AsyncServiceCallBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Tourze\AsyncServiceCallBundle\Exception\InvalidParameterException;
use Tourze\AsyncServiceCallBundle\Model\ObjectNormalizer;

/**
 * 之所以特地搞这个序列化组件，是为了兼容复杂的对象入参
 * 因为 Symfony 的序列化有自己的一套逻辑，所以要注意这样不要继承它
 */
#[WithMonologChannel(channel: 'async_service_call')]
class Serializer
{
    private \Symfony\Component\Serializer\Serializer $serializer;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ?EntityManagerInterface $entityManager = null,
    ) {
        $encoders = [new JsonEncoder()];
        $normalizers = [
            new BackedEnumNormalizer(),
            new DateTimeNormalizer(),
        ];
        if (null !== $this->entityManager) {
            $normalizers[] = new ObjectNormalizer($this->entityManager, $this->logger);
        }

        $this->serializer = new \Symfony\Component\Serializer\Serializer($normalizers, $encoders);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function serialize(mixed $data, string $format, array $context = []): string
    {
        return $this->serializer->serialize($data, $format, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function deserialize(mixed $data, string $type, string $format, array $context = []): mixed
    {
        return $this->serializer->deserialize($data, $type, $format, $context);
    }

    private function checkValue(mixed $value): void
    {
        // 这里还有一个问题，就是对于包含数组的对象，我们直接这样子序列化是不太对的
        if (is_array($value)) {
            foreach ($value as $item) {
                if (is_object($item)) {
                    throw new InvalidParameterException('使用异步注解时，参数请不要传入包含对象的数组');
                }
            }
        }
    }

    /**
     * 打包参数
     */
    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function encodeParams(array $params): array
    {
        $encodeParams = [];
        foreach ($params as $key => $value) {
            // Enum的特殊处理
            if ($value instanceof \BackedEnum) {
                $encodeParams[$key] = [
                    'enum',
                    get_class($value),
                    $value->value,
                ];
                continue;
            }

            $type = \gettype($value);
            switch ($type) {
                case 'object':
                    // 把对象转为一个json字符串
                    $encodeParams[$key] = [
                        'object',
                        get_class($value),
                        $this->serialize($value, 'json'),
                    ];
                    break;
                default:
                    $this->checkValue($value);
                    $encodeParams[$key] = [
                        $type,
                        $value,
                    ];
            }
        }

        return $encodeParams;
    }

    /**
     * @param array<string, mixed> $encodeParams
     * @return array<string, mixed>
     */
    public function decodeParams(array $encodeParams): array
    {
        $params = [];
        foreach ($encodeParams as $key => $value) {
            if ('enum' === $value[0]) {
                $className = $value[1];
                /* @var class-string<\BackedEnum> $className */
                $params[$key] = $className::from($value[2]);
                continue;
            }
            $params[$key] = match ($value[0]) {
                'object' => $this->deserialize($value[2], $value[1], 'json'),
                default => $value[1],
            };
        }

        return $params;
    }
}
