<?php

namespace Wexample\SymfonyRemote\Class;

use Wexample\PhpApi\Common\Client;
use Wexample\PhpApi\Common\ClientOptions;

/**
 * A client as configured, its environment placeholders resolved.
 */
final readonly class ClientDefinition
{
    /**
     * @param class-string<Client> $class
     * @param array<string, string|null> $headers
     * @param array{timeout: float, connect_timeout: float, retries: int, retry_delay: float, rate_limit_delay: float} $options
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $class,
        public ?string $baseUrl,
        public ?string $apiKey,
        public bool $apiKeyRequired,
        public array $headers,
        public array $options,
    ) {
    }

    /**
     * @return string[] the settings that resolved empty
     */
    public function getMissingSettings(): array
    {
        $missing = [];

        if (in_array($this->baseUrl, [null, ''], true)) {
            $missing[] = 'base_url';
        }

        if ($this->apiKeyRequired && in_array($this->apiKey, [null, ''], true)) {
            $missing[] = 'api_key';
        }

        return $missing;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return array_filter($this->headers, static fn (?string $value): bool => null !== $value && '' !== $value);
    }

    public function buildClientOptions(): ClientOptions
    {
        return new ClientOptions(
            timeout: $this->options['timeout'],
            connectTimeout: $this->options['connect_timeout'],
            retries: $this->options['retries'],
            retryDelay: $this->options['retry_delay'],
            rateLimitDelay: $this->options['rate_limit_delay'],
        );
    }
}
