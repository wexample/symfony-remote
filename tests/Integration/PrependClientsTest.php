<?php

declare(strict_types=1);

namespace Wexample\SymfonyRemote\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Wexample\SymfonyRemote\DependencyInjection\WexampleSymfonyRemoteExtension;

class PrependClientsTest extends TestCase
{
    public function testClientsArePrependedWithoutTheirNullOptions(): void
    {
        $container = new ContainerBuilder();

        WexampleSymfonyRemoteExtension::prependClients($container, [
            'git_main' => ['class' => 'App\\Client', 'base_url' => 'https://api.test', 'api_key' => null, 'api_key_required' => false],
        ]);

        $this->assertSame(
            [['clients' => ['git_main' => ['class' => 'App\\Client', 'base_url' => 'https://api.test', 'api_key_required' => false]]]],
            $container->getExtensionConfig('wexample_symfony_remote')
        );
    }

    public function testNoClientPrependsNothing(): void
    {
        $container = new ContainerBuilder();

        WexampleSymfonyRemoteExtension::prependClients($container, []);

        $this->assertSame([], $container->getExtensionConfig('wexample_symfony_remote'));
    }
}
