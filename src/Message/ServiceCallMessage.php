<?php

declare(strict_types=1);

namespace Tourze\AsyncServiceCallBundle\Message;

use Tourze\AsyncContracts\AsyncMessageInterface;

class ServiceCallMessage implements AsyncMessageInterface
{
    /**
     * @var string 服务ID
     */
    private string $serviceId;

    public function getServiceId(): string
    {
        return $this->serviceId;
    }

    public function setServiceId(string $serviceId): void
    {
        $this->serviceId = $serviceId;
    }

    /**
     * @var string 要执行的方法
     */
    private string $method;

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    /**
     * @var array<string, mixed> 参数
     */
    private array $params = [];

    /**
     * @return array<string, mixed>
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @param array<string, mixed> $params
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    private int $retryCount = 0;

    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    public function setRetryCount(int $retryCount): void
    {
        $this->retryCount = $retryCount;
    }

    private int $maxRetryCount = 0;

    public function getMaxRetryCount(): int
    {
        return $this->maxRetryCount;
    }

    public function setMaxRetryCount(int $maxRetryCount): void
    {
        $this->maxRetryCount = $maxRetryCount;
    }
}
