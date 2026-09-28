<?php

namespace Wexample\SymfonyRemote\Service;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Wexample\PhpApi\Common\Client;
use Wexample\SymfonyRemote\Class\ClientDefinition;
use Wexample\SymfonyRemote\Class\RateLimitMiddleware;

/**
 * Builds the php-api clients declared in configuration.
 */
class ApiClientFactory
{
    /**
     * @param HandlerStack|null $handlerStack Guzzle handlers to build on; tests pass a mock.
     */
    public function __construct(
        private readonly ?HandlerStack $handlerStack = null,
    ) {
    }

    public function create(
        ClientDefinition $definition,
        ?RateLimiterFactory $rateLimiterFactory = null,
    ): Client {
        $baseUrl = rtrim((string) $definition->baseUrl, '/').'/';
        $stack = $this->handlerStack ? clone $this->handlerStack : HandlerStack::create();

        if ($rateLimiterFactory) {
            $stack->push(
                new RateLimitMiddleware($rateLimiterFactory->create($definition->key)),
                'wexample_remote_rate_limit'
            );
        }

        $class = $definition->class;

        return new $class(
            $baseUrl,
            '' === $definition->apiKey ? null : $definition->apiKey,
            new GuzzleClient(['base_uri' => $baseUrl, 'handler' => $stack]),
            $definition->getHeaders(),
            $definition->buildClientOptions(),
        );
    }
}
