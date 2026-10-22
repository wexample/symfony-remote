<?php

namespace Wexample\SymfonyRemote\Tests\Fixtures\App;

use Wexample\SymfonyRemote\WexampleSymfonyRemoteBundle;
use Wexample\SymfonyTesting\Tests\Fixtures\AbstractFixtureKernel;

class AppKernel extends AbstractFixtureKernel
{
    protected function getFixtureDir(): string
    {
        return __DIR__;
    }

    protected function getExtraBundles(): iterable
    {
        return [
            new WexampleSymfonyRemoteBundle(),
        ];
    }

    protected function getConfigFiles(): array
    {
        return [
            __DIR__.'/config/config.yaml',
        ];
    }
}
