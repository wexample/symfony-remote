<?php

namespace Wexample\SymfonyRemote\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Wexample\SymfonyRemote\Service\RemoteRegistry;

class RemoteRegistryWiringTest extends KernelTestCase
{
    public function testServicesImplementingTheInterfaceAreRegistered(): void
    {
        $registry = self::getContainer()->get(RemoteRegistry::class);

        $keys = array_keys($registry->all());

        $this->assertContains('reachable', $keys);
        $this->assertContains('broken', $keys);
    }
}
