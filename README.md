# symfony-remote

Version: 2.0.1

## A php-api client from configuration

```yaml
# config/packages/wexample_symfony_remote.yaml
wexample_symfony_remote:
  clients:
    billing:
      class: App\Remote\BillingClient      # a php-api Client subclass; defaults to Client
      label: Billing API
      base_url: '%env(BILLING_API_URL)%'
      api_key: '%env(default::BILLING_API_KEY)%'   # declared, so required
      headers: { X-Tenant: acme }
      options: { timeout: 30, connect_timeout: 5, retries: 2, retry_delay: 1 }
      rate_limit: { policy: sliding_window, limit: 60, interval: '1 minute' }
```

Each entry gives:

- a client service, `wexample_symfony_remote.client.billing`, also aliased to its class when no other entry uses that class, so `BillingClient $client` autowires;
- a remote named `billing`, whose check requests the client's `PING_PATH` (the base URL unless the class redefines it).

The class must keep the constructor of php-api's `Client`: base URL, API key, Guzzle client, default headers, `ClientOptions`. A client whose constructor differs is built by the application and declared as a remote the other way, below.

`options` are php-api's `ClientOptions` in seconds; retries apply to idempotent methods only. `rate_limit` takes the options of a framework rate limiter and holds each request until a token is free, across every worker and web request of the app.

## Any other remote

A service implementing `RemoteInterface` is registered through autoconfiguration:

```php
final class RenderServerRemote implements RemoteInterface
{
    public function getKey(): string { return 'render_server'; }

    public function getLabel(): string { return 'Render server'; }

    public function checkStatus(): RemoteStatus
    {
        return $this->renderer->ping() ? RemoteStatus::up() : RemoteStatus::down('No answer.');
    }
}
```

A check may throw instead of returning `down()`: the registry reports the exception message. It also measures the latency, so a remote only says whether it answers.

## Checking

```bash
bin/console remote:status              # every remote, as a table
bin/console remote:status billing      # one remote
bin/console remote:status --format=json
```

The command fails when a remote is `Down`. `Unconfigured` does not fail it: missing credentials are a setup matter, not an outage. In code, `RemoteRegistry::checkAll()` and `check($key)` return the same `RemoteStatus` objects.

## Table of Contents

- [A php-api client from configuration](#a-php-api-client-from-configuration)
- [Any other remote](#any-other-remote)
- [Checking](#checking)
- [Architecture](#architecture)
- [Integration in the Suite](#integration-in-the-suite)
- [Dependencies](#dependencies)
- [Versioning & Compatibility Policy](#versioning--compatibility-policy)
- [License](#license)
- [About us](#about-us)
- [Migration Notes](#migration-notes)

## Architecture

The bundle is the Symfony integration of `wexample/php-remote`, which holds everything that does not need a framework: `RemoteInterface`, `RemoteStatus`, `RemoteState`, `RemoteRegistry`, `ClientDefinition`, `ApiClientFactory`, `ApiClientRemote` and the rate-limit middleware. What lives here, under `Wexample\SymfonyRemote\` (PSR-4 on `src/`), is the wiring: `DependencyInjection/` the configuration and the extension, `Class/` the rate limiter adapter, `Command/` the console entry point. It also depends on `symfony/rate-limiter` for shared quotas.

### Remotes and the registry

src/DependencyInjection/WexampleSymfonyRemoteExtension.php registers php-remote's `RemoteInterface` for autoconfiguration under `TAG_REMOTE`, so implementing it is enough, and defines php-remote's `RemoteRegistry` with the tagged services as its iterable. The registry itself — indexing by key, timing, turning a `Throwable` into `Down` — is php-remote's.

### Clients from configuration

src/DependencyInjection/Configuration.php declares `clients.<key>`. Header names keep their dashes (`normalizeKeys(false)`): Symfony would otherwise turn `X-Api-Key` into `X_Api_Key`. For each entry, the extension registers three things:

1. a `ClientDefinition`, the entry with its environment placeholders — resolved when the container builds it — and its `options` as php-api's `ClientOptions`. A declared `api_key` is a required one;
2. the client service `wexample_symfony_remote.client.<key>`, built by php-remote's `ApiClientFactory` (a service, so the tests can give it a mock handler stack). The class is aliased to the service when a single entry uses it;
3. an `ApiClientRemote` under the same key, tagged as a remote.

### Shared quotas

`prepend()` turns each `rate_limit` into a framework rate limiter named `wexample_remote_<key>`, with `lock_factory: auto` so that `symfony/lock` stays optional. Storage, policies and locking are the framework's own. The client service receives a src/Class/SymfonyRateLimiter.php, php-remote's `RateLimiterInterface` over the limiter created for the client's key: `acquire()` consumes a token, waiting for the window whenever it is refused. It consumes rather than reserves, since the sliding window policy cannot reserve.

php-api's own `rate_limit_delay` spaces the requests of one client instance, which is one PHP process; `rate_limit` counts across all of them.

### The command

src/Command/StatusCommand.php extends `AbstractBundleCommand`, which names it `remote:status` from the bundle and the class. It prints a table or JSON and returns `FAILURE` as soon as one status is `Down`.

### Tests

Integration tests only: the registry, the clients and the middleware are unit-tested in php-remote. `tests/Fixtures/App/AppKernel.php` boots the bundle on `symfony-testing`'s fixture kernel. Its configuration declares three clients — one complete, one missing its URL, one with a quota — and replaces the Guzzle handlers of `ApiClientFactory` by a shared `MockHandler`, so every client answers from a queue the tests fill.

## Integration in the Suite

This package is part of the Wexample Suite — a collection of high-quality, modular tools designed to work seamlessly together across multiple languages and environments.

### Related Packages

The suite includes packages for configuration management, file handling, prompts, and more. Each package can be used independently or as part of the integrated suite.

Visit the [Wexample Suite documentation](https://docs.wexample.com) for the complete package ecosystem.

## Dependencies

- php: >=8.5
- symfony/rate-limiter: ^7.4
- wexample/php-api: >=5.0.0
- wexample/php-remote: >=1.0.2
- wexample/symfony-helpers: >=13.0.0

## Versioning & Compatibility Policy

Wexample packages follow **Semantic Versioning** (SemVer):

- **MAJOR**: Breaking changes
- **MINOR**: New features, backward compatible
- **PATCH**: Bug fixes, backward compatible

We maintain backward compatibility within major versions and provide clear migration guides for breaking changes.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

Free to use in both personal and commercial projects.

## About us

[Wexample](https://wexample.com) stands as a cornerstone of the digital ecosystem — a collective of seasoned engineers, researchers, and creators driven by a relentless pursuit of technological excellence. More than a media platform, it has grown into a vibrant community where innovation meets craftsmanship, and where every line of code reflects a commitment to clarity, durability, and shared intelligence.

This packages suite embodies this spirit. Trusted by professionals and enthusiasts alike, it delivers a consistent, high-quality foundation for modern development — open, elegant, and battle-tested. Its reputation is built on years of collaboration, refinement, and rigorous attention to detail, making it a natural choice for those who demand both robustness and beauty in their tools.

Wexample cultivates a culture of mastery. Each package, each contribution carries the mark of a community that values precision, ethics, and innovation — a community proud to shape the future of digital craftsmanship.

## Migration Notes

When upgrading between major versions, refer to the migration guides in the documentation.

Breaking changes are clearly documented with upgrade paths and examples.
