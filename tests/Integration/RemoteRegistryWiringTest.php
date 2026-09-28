<?php

namespace Wexample\SymfonyRemote\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Wexample\SymfonyRemote\Service\RemoteRegistry;

class RemoteRegistryWiringTest extends KernelTestCase
{
    public function testServicesImplementingTheInterfaceAreRegistered(): void
    {
        $registry = self::getContainer()->get(RemoteRegistry::class);

        $this->assertEqualsCanonicalizing(['reachable', 'broken'], array_keys($registry->all()));
    }
}
