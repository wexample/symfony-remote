<?php

namespace Wexample\SymfonyRemote\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Wexample\SymfonyRemote\WexampleSymfonyRemoteBundle;

class KernelBootTest extends KernelTestCase
{
    public function testTheBundleBootsInTheFixtureKernel(): void
    {
        $kernel = self::bootKernel();

        $this->assertInstanceOf(
            WexampleSymfonyRemoteBundle::class,
            $kernel->getBundle('WexampleSymfonyRemoteBundle')
        );
    }
}
