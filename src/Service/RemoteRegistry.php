<?php

namespace Wexample\SymfonyRemote\Service;

use LogicException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Throwable;
use Wexample\SymfonyRemote\Class\RemoteStatus;
use Wexample\SymfonyRemote\Interface\RemoteInterface;

class RemoteRegistry
{
    /**
     * @var array<string, RemoteInterface>
     */
    private array $remotes = [];

    /**
     * @param iterable<RemoteInterface> $remotes
     */
    public function __construct(
        #[AutowireIterator(RemoteInterface::TAG)]
        iterable $remotes,
    ) {
        foreach ($remotes as $remote) {
            $key = $remote->getKey();

            if (isset($this->remotes[$key])) {
                throw new LogicException(sprintf(
                    'Two remotes share the key "%s": %s and %s.',
                    $key,
                    $this->remotes[$key]::class,
                    $remote::class
                ));
            }

            $this->remotes[$key] = $remote;
        }
    }

    /**
     * @return array<string, RemoteInterface>
     */
    public function all(): array
    {
        return $this->remotes;
    }

    public function get(string $key): RemoteInterface
    {
        return $this->remotes[$key] ?? throw new LogicException(sprintf(
            'No remote "%s". Registered: %s.',
            $key,
            implode(', ', array_keys($this->remotes)) ?: 'none'
        ));
    }

    /**
     * Times the remote's own check. A remote that throws is reported Down with
     * the exception message: one broken remote must not hide the others.
     */
    public function check(string $key): RemoteStatus
    {
        $remote = $this->get($key);
        $start = hrtime(true);

        try {
            $status = $remote->checkStatus();
        } catch (Throwable $exception) {
            $status = RemoteStatus::down($exception->getMessage());
        }

        return $status->withLatency((hrtime(true) - $start) / 1_000_000);
    }

    /**
     * @return array<string, RemoteStatus>
     */
    public function checkAll(): array
    {
        $statuses = [];

        foreach (array_keys($this->remotes) as $key) {
            $statuses[$key] = $this->check($key);
        }

        return $statuses;
    }
}
