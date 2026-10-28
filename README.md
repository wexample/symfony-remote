# symfony-remote

Version: 2.1.1

Registry, health checks and configuration of the external services a Symfony app talks to

## Table of Contents

- [Installation](#installation)
- [Introduction](#introduction)

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

`symfony-remote` is a Symfony bundle for everything an application talks to outside itself — an HTTP API, an SDK, a binary. It keeps what those have in common: a registry of the app's remotes, a status per remote (up, down, or unconfigured when its credentials are missing), and `remote:status`, a command that checks them all and fails when one is down, so a cron or a probe can run it as is.

It is not a transport. Any service implementing `RemoteInterface` is a remote, whatever library it talks through. For HTTP APIs built on `wexample/php-api`, which carry the most boilerplate, a client is declared in configuration instead: base URL, key, headers, timeouts and retries, and a quota shared by every process of the app, built on Symfony's rate limiter.

The framework-free part — the remote contract, the registry, the statuses and the php-api client building — is `wexample/php-remote`; this bundle wires it into Symfony.

Screens to watch and test the remotes live in `symfony-remote-ds`; clients of one given service in `symfony-remote-<service>`.
