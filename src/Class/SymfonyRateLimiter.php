<?php

namespace Wexample\SymfonyRemote\Class;

use Symfony\Component\RateLimiter\LimiterInterface;
use Wexample\PhpRemote\Interface\RateLimiterInterface;

/**
 * A framework rate limiter as php-remote's quota: its storage is the app's,
 * so the quota counts the requests of every process of the app.
 */
final readonly class SymfonyRateLimiter implements RateLimiterInterface
{
    public function __construct(
        private LimiterInterface $limiter,
    ) {
    }

    /**
     * Consumes rather than reserves: the sliding window policy cannot reserve.
     */
    public function acquire(): void
    {
        $limit = $this->limiter->consume();

        while (! $limit->isAccepted()) {
            $limit->wait();
            $limit = $this->limiter->consume();
        }
    }
}
