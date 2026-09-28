<?php

namespace Wexample\SymfonyRemote\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('wexample_symfony_remote');

        $treeBuilder->getRootNode()
            ->children()
            ->arrayNode('clients')
            ->info('A php-api client per entry, named by its key, registered as a service and as a remote.')
            ->useAttributeAsKey('key')
            ->arrayPrototype()
            ->end()
            ->end()
            ->end();

        return $treeBuilder;
    }
}
