<?php

namespace Tourze\AsyncServiceCallBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AsyncServiceCallBundle\Exception\InvalidParameterException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(InvalidParameterException::class)]
final class InvalidParameterExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionCanBeThrown(): void
    {
        $exception = new InvalidParameterException('Test message');

        $this->assertInstanceOf(InvalidParameterException::class, $exception);
        $this->assertSame('Test message', $exception->getMessage());
    }
}
