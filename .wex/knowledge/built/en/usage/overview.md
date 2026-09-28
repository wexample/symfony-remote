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
