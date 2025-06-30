<?php

namespace Tourze\AsyncServiceCallBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\AsyncServiceCallBundle\Exception\InvalidParameterException;

class InvalidParameterExceptionTest extends TestCase
{
    public function testExceptionCanBeThrown(): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Test message');
        
        throw new InvalidParameterException('Test message');
    }
    
    public function testExceptionIsInstanceOfRuntimeException(): void
    {
        $exception = new InvalidParameterException('Test');
        
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
}