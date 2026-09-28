<?php

namespace Wexample\SymfonyRemote\Class;

use Psr\Http\Message\RequestInterface;
use Symfony\Component\RateLimiter\LimiterInterface;

/**
 * Guzzle middleware holding each request until the shared limiter grants a
 * token, so the quota counts the requests of every process of the app.
 */
final readonly class RateLimitMiddleware
{
    public function __construct(
        private LimiterInterface $limiter,
    ) {
    }

    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $limit = $this->limiter->consume();

            while (! $limit->isAccepted()) {
                $limit->wait();
                $limit = $this->limiter->consume();
            }

            return $handler($request, $options);
        };
    }
}
