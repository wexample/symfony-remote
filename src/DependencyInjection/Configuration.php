<?php

namespace Wexample\SymfonyRemote\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Wexample\PhpApi\Common\Client;

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
                        ->children()
                            ->scalarNode('class')
                                ->defaultValue(Client::class)
                                ->info('A php-api Client subclass whose constructor keeps Client\'s own: base URL, API key, Guzzle client, default headers, options.')
                            ->end()
                            ->scalarNode('label')->defaultNull()->info('Defaults to the key.')->end()
                            ->scalarNode('base_url')->defaultNull()->end()
                            ->scalarNode('api_key')
                                ->defaultNull()
                                ->info('Declared, it is required: the remote reads Unconfigured while it resolves empty.')
                            ->end()
                            ->arrayNode('headers')
                                ->info('Sent with every request; a header resolving empty is left out.')
                                // Header names keep their dashes: X-Api-Key, not X_Api_Key.
                                ->normalizeKeys(false)
                                ->useAttributeAsKey('name')
                                ->scalarPrototype()->end()
                            ->end()
                            ->arrayNode('options')
                                ->info('php-api ClientOptions, in seconds.')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->floatNode('timeout')->defaultValue(0.0)->end()
                                    ->floatNode('connect_timeout')->defaultValue(0.0)->end()
                                    ->integerNode('retries')->defaultValue(0)->min(0)->end()
                                    ->floatNode('retry_delay')->defaultValue(1.0)->end()
                                    ->floatNode('rate_limit_delay')->defaultValue(0.0)->end()
                                ->end()
                            ->end()
                            ->arrayNode('rate_limit')
                                ->info('A quota shared by every process of the app, as a framework rate limiter: policy, limit, interval, rate.')
                                ->variablePrototype()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
