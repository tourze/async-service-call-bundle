<?php

declare(strict_types=1);

namespace Tourze\AsyncServiceCallBundle\DependencyInjection;

use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

class AsyncServiceCallExtension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }
}
