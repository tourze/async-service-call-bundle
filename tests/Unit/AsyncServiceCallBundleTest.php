<?php

namespace Tourze\AsyncServiceCallBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tourze\AsyncServiceCallBundle\AsyncServiceCallBundle;

class AsyncServiceCallBundleTest extends TestCase
{
    public function testBundleCanBeInstantiated(): void
    {
        $bundle = new AsyncServiceCallBundle();
        
        $this->assertInstanceOf(AsyncServiceCallBundle::class, $bundle);
    }
}