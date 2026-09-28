# symfony-remote: scaffold and build the remote registry

Opened: before 2026-09
Updated: 2026-09-28
Author: agent, revised with the owner on 2026-09-28

## Status

Proposal written on 2026-09-28, to be validated with the owner before any code. The open
questions at the end need an answer first. Then stop and report after step 1.

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
  ```

  Each entry becomes a client service `wexample_symfony_remote.client.<key>`, with an alias on
  its class when a single entry uses that class so it can be autowired, plus an
  `ApiClientRemote` registered under the same key. A missing `base_url` or `api_key` gives
  `Unconfigured` instead of a boot failure.
- **Command `remote:status [key] [--format=table|json]`**: checks every remote, or one of
  them. The exit code is non-zero when one is `Down`, so it can run from cron or a probe.
- **Credentials**: environment variables and Symfony secrets only in v1, read through the
  configuration above. No credentials entity yet (see questions).

What stays out of this package:
- per-user authentication, such as Syrtis' user token kept in the session: it depends on the
  request, so it stays in the app, which asks the configured client for a copy carrying the
  user's token;
- screens and the "Test connection" button: `symfony-remote-ds`;
- demo pages: `symfony-remote-demo`;
- service-specific clients: `symfony-remote-<service>`.

## Steps (each ends with green tests and one commit)

1. **Scaffolding.** Complete `composer.json` (`require`: `symfony-helpers`, `php-api`;
   `require-dev`: `phpunit`, `symfony-testing`), bundle class extending `AbstractBundle`,
   extension, `Configuration`, `services.yaml` autoconfiguring every service directory,
   `phpunit.xml` and `tests/Fixtures/App/AppKernel` modelled on `symfony-loader`. The kernel
   boots. **Checkpoint: report to the owner.**
2. **Registry.** `RemoteInterface`, `RemoteStatus`, `RemoteState`, the tag, `RemoteRegistry`.
   Tests: two fixture remotes, one throwing.
3. **php-api clients from configuration.** `ApiClientRemote`, config tree, client services and
   aliases, `ClientOptions` mapping. Tests with a Guzzle `MockHandler`: up, down, unconfigured,
   options applied.
4. **Command.** `remote:status` with table and JSON output and the exit code. Tests.
5. **Docs.** The four standard knowledge pages; a cookbook page "Declare a remote" covering
   the three transports above.
6. **First adopters** (only once validated, since it touches neighbours): `symfony-wex`
   declares its agent server and binary as remotes.

## Open questions for the owner

1. Credentials: environment and secrets only in v1, with an encrypted credentials entity
   later, alongside `remote-ds`? (recommended)
2. Status history: v1 checks live and stores nothing. A table of past checks would come with
   `remote-ds` if needed. (recommended)
3. Cross-process rate limiting: `php-api`'s `rateLimitDelay` works per client instance, hence
   per PHP process. Is it enough, or do we plug `symfony/rate-limiter` in for limits shared
   between workers? Recommended: not in v1.
4. Step 6: may `symfony-wex` adopt the package right away, or later?
