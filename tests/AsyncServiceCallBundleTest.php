<?php

declare(strict_types=1);

namespace Tourze\AsyncServiceCallBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AsyncServiceCallBundle\AsyncServiceCallBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(AsyncServiceCallBundle::class)]
#[RunTestsInSeparateProcesses]
final class AsyncServiceCallBundleTest extends AbstractBundleTestCase
{
}
