<?php

namespace Wexample\SymfonyRemote\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Wexample\SymfonyHelpers\DependencyInjection\AbstractWexampleSymfonyExtension;
use Wexample\SymfonyRemote\Interface\RemoteInterface;

class WexampleSymfonyRemoteExtension extends AbstractWexampleSymfonyExtension
{
    public function load(
        array $configs,
        ContainerBuilder $container
    ): void {
        $this->loadConfig(
            __DIR__,
            $container
        );

        $this->processConfiguration(new Configuration(), $configs);

        $container
            ->registerForAutoconfiguration(RemoteInterface::class)
            ->addTag(RemoteInterface::TAG);
    }
}
