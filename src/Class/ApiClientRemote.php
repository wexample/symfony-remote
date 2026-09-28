<?php

namespace Wexample\SymfonyRemote\Class;

use Wexample\PhpApi\Common\Client;
use Wexample\PhpApi\Enum\HttpMethod;
use Wexample\SymfonyRemote\Interface\RemoteInterface;

/**
 * The remote of a php-api client declared in configuration.
 */
final readonly class ApiClientRemote implements RemoteInterface
{
    public function __construct(
        private ClientDefinition $definition,
        private Client $client,
    ) {
    }

    public function getKey(): string
    {
        return $this->definition->key;
    }

    public function getLabel(): string
    {
        return $this->definition->label;
    }

    /**
     * Requests the client's PING_PATH. An error status or a transport failure
     * raises php-api's ApiException, which the registry reports as Down with
     * its message — more useful than checkConnection()'s bare boolean.
     */
    public function checkStatus(): RemoteStatus
    {
        $missing = $this->definition->getMissingSettings();

        if ([] !== $missing) {
            return RemoteStatus::unconfigured('Missing: '.implode(', ', $missing).'.');
        }

        $this->client->request(HttpMethod::GET, $this->client::PING_PATH);

        return RemoteStatus::up();
    }
}
