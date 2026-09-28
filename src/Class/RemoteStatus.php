<?php

namespace Wexample\SymfonyRemote\Class;

use DateTimeImmutable;
use Wexample\SymfonyRemote\Enum\RemoteState;

final readonly class RemoteStatus
{
    /**
     * @param float|null $latency Milliseconds the check took, measured by the registry.
     */
    public function __construct(
        public RemoteState $state,
        public ?string $message = null,
        public ?float $latency = null,
        public DateTimeImmutable $checkedAt = new DateTimeImmutable(),
    ) {
    }

    public static function up(?string $message = null): self
    {
        return new self(RemoteState::Up, $message);
    }

    public static function down(string $message): self
    {
        return new self(RemoteState::Down, $message);
    }

    public static function unconfigured(string $message): self
    {
        return new self(RemoteState::Unconfigured, $message);
    }

    /**
     * The shape the console and the screens report a status in.
     *
     * @return array{state: string, message: string|null, latency: float|null, checkedAt: string}
     */
    public function toArray(): array
    {
        return [
            'state' => $this->state->value,
            'message' => $this->message,
            'latency' => $this->latency,
            'checkedAt' => $this->checkedAt->format(DATE_ATOM),
        ];
    }

    public function withLatency(float $latency): self
    {
        return new self($this->state, $this->message, $latency, $this->checkedAt);
    }
}
