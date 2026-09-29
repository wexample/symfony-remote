# symfony-remote: scaffold and build the remote registry

Opened: before 2026-09
Updated: 2026-09-29
Author: agent, revised with the owner on 2026-09-28

## Status

2026-09-29: the framework-free part — `RemoteInterface`, `RemoteStatus`, `RemoteState`,
`RemoteRegistry`, `ClientDefinition`, `ApiClientFactory`, `ApiClientRemote`, the rate-limit
middleware — moved to `php-remote` (`Wexample\PhpRemote\`), behind a `RateLimiterInterface`
this bundle implements with `SymfonyRateLimiter`. Class names below are the pre-move ones.

Validated with the owner on 2026-09-28 (decisions at the end). All steps done on
2026-09-28: 16 tests green; `symfony-wex` declares `AgentServerRemote` and `WexBinaryRemote`
(checked through the registry with a mock HTTP client and fake binaries — `symfony-wex` has
no test suite of its own). Next: `symfony-remote-ds` and `symfony-remote-demo`.

Found while building step 3: a configured client must keep php-api `Client`'s constructor
(base URL, API key, Guzzle client, headers, options). `SyrtisClient` does not — it takes a host
and an API version — so Syrtis cannot be declared in configuration as the example below
shows; it would be built by the app and declared as a `RemoteInterface` service instead,
unless its constructor is aligned on `Client`'s.

Paths are relative to the PHP suite root (`PACKAGES/PHP/packages/wexample/`). Read
`symfony-tunnels/.wex/journal/todo/todo-common.md` first: layout, tests, docs, naming.

## What this package is for

One Symfony package for everything the app talks to outside itself: an HTTP API, an SDK, a
binary. Postgres/Doctrine is out of scope: it has its own health checking.

It is **not a transport**. The suite already reaches remotes three ways, and all three must
fit:

| Remote | Transport | Where |
|---|---|---|
| Syrtis API | `php-api` (Guzzle), entity layer from `php-api-entity` | `SYRTIS/local/manager/src/Service/Syrtis/SyrtisClientProvider.php` builds it by hand |
| wex agent server | Symfony `HttpClientInterface`, streamed | `symfony-wex/src/Service/AgentServerClient.php` |
| wex binary | a process | `php-wex` `WexClient`, wired in `symfony-wex` |

So the package owns what is common to all remotes: **a registry, a status, a health check,
credentials**. On top of that, it gives HTTP APIs built on `php-api` a ready-made wiring
from configuration, since that is the case with the most boilerplate today.

## Design

- **`Interface/RemoteInterface`**: `getKey(): string`, `getLabel(): string`,
  `checkStatus(): RemoteStatus`. Any service implementing it is a remote, whatever its
  transport: autoconfiguration tags it `wexample_symfony_remote.remote`, and no central list
  has to be kept.
- **`Class/RemoteStatus`**: `Enum/RemoteState` (Up, Down, Unconfigured), a message, a latency
  in ms, a check date. `Unconfigured` is for a remote whose credentials are missing: that is
  a configuration problem, not an outage, and must not page anyone.
- **`Service/RemoteRegistry`**: collects the tagged remotes and checks one or all of them.
  One remote throwing must not hide the others: a check that fails reports `Down` with the
  exception message.
- **`Class/ApiClientRemote`**: the adapter for `php-api` clients. `checkStatus()` times
  `Client::checkConnection()`.
- **Clients from configuration**, which replaces the static half of `SyrtisClientProvider`:

  ```yaml
  wexample_symfony_remote:
    clients:
      syrtis:
        class: SyrtisClientInternal\Common\SyrtisClient   # any php-api Client subclass
        label: Syrtis
        base_url: '%env(SYRTIS_API_URL)%'
        api_key: '%env(default::SYRTIS_API_TOKEN)%'
        headers: { Host: '%env(default::SYRTIS_API_HOST)%' }
        options: { timeout: 30, retries: 2, retry_delay: 1, rate_limit_delay: 0 }
        rate_limit: { policy: sliding_window, limit: 60, interval: '1 minute' }
  ```

  Each entry becomes a client service `wexample_symfony_remote.client.<key>`, with an alias on
  its class when a single entry uses that class so it can be autowired, plus an
  `ApiClientRemote` registered under the same key. A missing `base_url` or `api_key` gives
  `Unconfigured` instead of a boot failure.
- **Shared rate limit**: `rate_limit` builds a `symfony/rate-limiter` limiter keyed by the
  client, stored in the app cache, so the quota holds across every worker and web request,
  not per PHP process like php-api's `rate_limit_delay`. It is applied by a Guzzle middleware
  pushed on the client's handler stack (the client receives that Guzzle client through its
  `$httpClient` argument), which waits for a token before each request: it works for any
  php-api client class without subclassing it.
- **Command `remote:status [key] [--format=table|json]`**: checks every remote, or one of
  them. The exit code is non-zero when one is `Down`, so it can run from cron or a probe.
- **Credentials**: environment variables and Symfony secrets only in v1, read through the
  configuration above. No credentials entity yet (see Decisions).

What stays out of this package:
- per-user authentication, such as Syrtis' user token kept in the session: it depends on the
  request, so it stays in the app, which asks the configured client for a copy carrying the
  user's token;
- screens and the "Test connection" button: `symfony-remote-ds`;
- demo pages: `symfony-remote-demo`;
- service-specific clients: `symfony-remote-<service>`.

## Steps (each ends with green tests and one commit)

1. **Scaffolding.** Complete `composer.json` (`require`: `symfony-helpers`, `php-api`,
   `symfony/rate-limiter`;
   `require-dev`: `phpunit`, `symfony-testing`), bundle class extending `AbstractBundle`,
   extension, `Configuration`, `services.yaml` autoconfiguring every service directory,
   `phpunit.xml` and `tests/Fixtures/App/AppKernel` modelled on `symfony-loader`. The kernel
   boots. **Checkpoint: report to the owner.**
2. **Registry.** `RemoteInterface`, `RemoteStatus`, `RemoteState`, the tag, `RemoteRegistry`.
   Tests: two fixture remotes, one throwing.
3. **php-api clients from configuration.** `ApiClientRemote`, config tree, client services and
   aliases, `ClientOptions` mapping, the rate-limit middleware. Tests with a Guzzle
   `MockHandler`: up, down, unconfigured, options applied, a request waiting for the limiter.
4. **Command.** `remote:status` with table and JSON output and the exit code. Tests.
5. **Docs.** The four standard knowledge pages; a cookbook page "Declare a remote" covering
   the three transports above.
6. **First adopters** (only once validated, since it touches neighbours): `symfony-wex`
   declares its agent server and binary as remotes.

## Decisions (2026-09-28)

1. Credentials: environment variables and Symfony secrets only in v1. An encrypted
   credentials entity may come later with `remote-ds`.
2. Status history: none in v1, checks are live.
3. Shared rate limiting with `symfony/rate-limiter`: in v1 (see Design).
4. Step 6: `symfony-wex` declares its agent server (`AgentServerClient`, HTTP) and the wex
   binary (`WexClient`, process) as remotes — to confirm with the owner when step 5 is done.
