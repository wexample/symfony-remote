<?php

namespace Wexample\SymfonyRemote\DependencyInjection;

use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\RateLimiter\LimiterInterface;
use Wexample\PhpApi\Common\ClientOptions;
use Wexample\PhpRemote\Class\ApiClientFactory;
use Wexample\PhpRemote\Class\ApiClientRemote;
use Wexample\PhpRemote\Class\ClientDefinition;
use Wexample\PhpRemote\Class\RemoteRegistry;
use Wexample\PhpRemote\Interface\RemoteInterface;
use Wexample\SymfonyHelpers\DependencyInjection\AbstractWexampleSymfonyExtension;
use Wexample\SymfonyRemote\Class\SymfonyRateLimiter;

class WexampleSymfonyRemoteExtension extends AbstractWexampleSymfonyExtension
{
    public const string SERVICE_CLIENT_PREFIX = 'wexample_symfony_remote.client.';

    public const string SERVICE_REMOTE_PREFIX = 'wexample_symfony_remote.remote.';

    public const string TAG_REMOTE = 'wexample_symfony_remote.remote';

    private const string LIMITER_PREFIX = 'wexample_remote_';

    /**
     * Declares each client's quota as a framework rate limiter, so storage,
     * policies and locking are the framework's own rather than a copy of them.
     */
    public function prepend(ContainerBuilder $container): void
    {
        parent::prepend($container);

        $config = $this->processConfiguration(
            new Configuration(),
            $container->getExtensionConfig($this->getAlias())
        );

        $limiters = [];
        foreach ($config['clients'] as $key => $client) {
            if ([] !== $client['rate_limit']) {
                // Without symfony/lock the framework would refuse the limiter;
                // "auto" uses a lock only where the app configured one.
                $limiters[self::LIMITER_PREFIX.$key] = $client['rate_limit'] + ['lock_factory' => 'auto'];
            }
        }

        if ([] !== $limiters) {
            $container->prependExtensionConfig('framework', ['rate_limiter' => $limiters]);
        }
    }

    public function load(
        array $configs,
        ContainerBuilder $container
    ): void {
        $this->loadConfig(
            __DIR__,
            $container
        );

        $config = $this->processConfiguration(new Configuration(), $configs);

        // Implementing the interface is enough to be a remote.
        $container
            ->registerForAutoconfiguration(RemoteInterface::class)
            ->addTag(self::TAG_REMOTE);

        $container->setDefinition(
            RemoteRegistry::class,
            new Definition(RemoteRegistry::class, [new TaggedIteratorArgument(self::TAG_REMOTE)])
        );

        $classCounts = array_count_values(array_column($config['clients'], 'class'));

        foreach ($config['clients'] as $key => $client) {
            $this->registerClient($container, (string) $key, $client, 1 === $classCounts[$client['class']]);
        }
    }

    private function registerClient(
        ContainerBuilder $container,
        string $key,
        array $client,
        bool $aliasClass
    ): void {
        $options = $client['options'];
        $definition = new Definition(ClientDefinition::class, [
            $key,
            $client['label'] ?? $key,
            $client['class'],
            $client['base_url'],
            $client['api_key'],
            null !== $client['api_key'],
            $client['headers'],
            new Definition(ClientOptions::class, [
                $options['timeout'],
                $options['connect_timeout'],
                $options['retries'],
                $options['retry_delay'],
                $options['rate_limit_delay'],
            ]),
        ]);

        $clientId = self::SERVICE_CLIENT_PREFIX.$key;
        $container
            ->setDefinition($clientId, new Definition($client['class']))
            ->setFactory([new Reference(ApiClientFactory::class), 'create'])
            ->setArguments([
                $definition,
                [] !== $client['rate_limit']
                    ? $this->buildRateLimiter($key)
                    : null,
            ])
            ->setPublic(true);

        // Two entries sharing a class could not both answer to it.
        if ($aliasClass) {
            $container->setAlias($client['class'], $clientId);
        }

        $container
            ->setDefinition(self::SERVICE_REMOTE_PREFIX.$key, new Definition(ApiClientRemote::class, [
                $definition,
                new Reference($clientId),
            ]))
            ->addTag(self::TAG_REMOTE);
    }

    /**
     * The framework limiter declared in prepend(), created for the client's key.
     */
    private function buildRateLimiter(string $key): Definition
    {
        return new Definition(SymfonyRateLimiter::class, [
            (new Definition(LimiterInterface::class))
                ->setFactory([new Reference('limiter.'.self::LIMITER_PREFIX.$key), 'create'])
                ->setArguments([$key]),
        ]);
    }
}
