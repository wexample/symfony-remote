## Installation

### Bundle

```php
// config/bundles.php
Wexample\SymfonyRemote\WexampleSymfonyRemoteBundle::class => ['all' => true],
```

Nothing else is required: with no configuration, the registry holds the services the app declares as remotes, and `remote:status` checks them.

### Credentials

Credentials come from environment variables or Symfony secrets, through the configuration. Use `%env(default::NAME)%` for a value that may be absent: the remote then reads `Unconfigured` instead of the container failing to boot.

### Shared quotas

A client with a `rate_limit` becomes a framework rate limiter stored in the `cache.rate_limiter` pool. A lock is used only where the app configured `symfony/lock`; without one, two processes may both take the last token of a window, which is acceptable for a quota and not for a counter.
