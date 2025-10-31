<?php

namespace Tourze\AsyncServiceCallBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AsyncServiceCallBundle\Exception\ServiceNotFoundException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(ServiceNotFoundException::class)]
final class ServiceNotFoundExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionCanBeThrown(): void
    {
        $exception = new ServiceNotFoundException('Service not found');

        $this->assertInstanceOf(ServiceNotFoundException::class, $exception);
        $this->assertSame('Service not found', $exception->getMessage());
    }
}
