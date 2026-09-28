## Declare a remote

Three remotes the suite already has, one per transport.

### An HTTP API on php-api

Configuration only, as in the usage page:

```yaml
wexample_symfony_remote:
  clients:
    billing:
      class: App\Remote\BillingClient
      base_url: '%env(BILLING_API_URL)%'
      api_key: '%env(default::BILLING_API_KEY)%'
```

Give the class a `PING_PATH` when the base URL itself does not answer `2xx`:

```php
final class BillingClient extends AbstractApiClient
{
    public const string PING_PATH = 'health';
}
```

A client whose constructor differs from php-api `Client`'s — Syrtis' `SyrtisClient` takes a host and an API version — is built by the application and declared as below, wrapping its own `checkConnection()`.

### An HTTP client on Symfony's HttpClient

The wex agent server client (`symfony-wex`'s `AgentServerClient`) streams through `HttpClientInterface`. Its package adds a remote next to it:

```php
final readonly class AgentServerRemote implements RemoteInterface
{
    public function __construct(private HttpClientInterface $httpClient, private ?string $url) {}

    public function getKey(): string { return 'wex_agent_server'; }

    public function getLabel(): string { return 'wex agent server'; }

    public function checkStatus(): RemoteStatus
    {
        if (!$this->url) {
            return RemoteStatus::unconfigured('Missing: agent_server.url.');
        }

        $this->httpClient->request('GET', $this->url)->getStatusCode(); // throws on failure

        return RemoteStatus::up();
    }
}
```

### A binary

A process has no connection, but it may be missing or broken. The check runs the cheapest command it has:

```php
public function checkStatus(): RemoteStatus
{
    $process = new Process([$this->binary, '--version']);
    $process->run();

    return $process->isSuccessful()
        ? RemoteStatus::up(trim($process->getOutput()))
        : RemoteStatus::down(trim($process->getErrorOutput()) ?: 'Exit code '.$process->getExitCode());
}
```
