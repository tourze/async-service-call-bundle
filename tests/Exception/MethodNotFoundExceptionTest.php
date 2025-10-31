<?php

namespace Tourze\AsyncServiceCallBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AsyncServiceCallBundle\Exception\MethodNotFoundException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(MethodNotFoundException::class)]
final class MethodNotFoundExceptionTest extends AbstractExceptionTestCase
{
    public function testException(): void
    {
        $exception = new MethodNotFoundException('Test message');

        $this->assertInstanceOf(MethodNotFoundException::class, $exception);
        $this->assertSame('Test message', $exception->getMessage());
    }
}
