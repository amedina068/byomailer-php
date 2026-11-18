<?php

declare(strict_types=1);

namespace Byomailer\Data;

use DateTimeImmutable;

/**
 * A URL that receives the webhook deliveries of the selected events.
 *
 * The instance sends the secret only when it creates the endpoint and when it
 * rotates the secret. Store the secret at that time. In the other responses,
 * `secret` is null.
 */
final readonly class WebhookEndpoint
{
    /**
     * @param  list<string>  $events
     */
    public function __construct(
        public int $id,
        public string $uuid,
        public string $url,
        public array $events,
        public bool $active,
        public ?string $description = null,
        public ?string $disabledReason = null,
        public int $consecutiveFailures = 0,
        public ?DateTimeImmutable $lastTriggeredAt = null,
        public ?DateTimeImmutable $disabledAt = null,
        public ?DateTimeImmutable $createdAt = null,
        #[\SensitiveParameter] public ?string $secret = null,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $payload = Payload::of($data);

        return new self(
            id: $payload->int('id'),
            uuid: $payload->string('uuid'),
            url: $payload->string('url'),
            events: $payload->strings('events'),
            active: $payload->bool('active'),
            description: $payload->nullableString('description'),
            disabledReason: $payload->nullableString('disabled_reason'),
            consecutiveFailures: $payload->nullableInt('consecutive_failures') ?? 0,
            lastTriggeredAt: $payload->date('last_triggered_at'),
            disabledAt: $payload->date('disabled_at'),
            createdAt: $payload->date('created_at'),
            secret: $payload->nullableString('secret'),
        );
    }

    /**
     * Keep the secret out of `var_dump()` and `dd()` output.
     *
     * @return array<mixed>
     */
    public function __debugInfo(): array
    {
        $properties = get_object_vars($this);

        if ($this->secret !== null) {
            $properties['secret'] = '********';
        }

        return $properties;
    }
}
