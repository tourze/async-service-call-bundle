<?php

namespace Tourze\AsyncServiceCallBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AsyncServiceCallBundle\Exception\ServiceExecutionException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(ServiceExecutionException::class)]
final class ServiceExecutionExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionCanBeThrown(): void
    {
        $exception = new ServiceExecutionException('Test message');

        $this->assertInstanceOf(ServiceExecutionException::class, $exception);
        $this->assertSame('Test message', $exception->getMessage());
    }
}
