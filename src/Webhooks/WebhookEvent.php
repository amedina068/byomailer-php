<?php

declare(strict_types=1);

namespace Byomailer\Webhooks;

use DateTimeImmutable;
use Throwable;

/**
 * A verified webhook delivery.
 *
 * Each payload has `event` and `occurred_at`. The other keys depend on the
 * event type. The instance can add event types, so do not reject a type that
 * you do not know.
 */
final readonly class WebhookEvent
{
    public const CONTACT_SUBSCRIBED = 'contact.subscribed';

    public const CONTACT_UNSUBSCRIBED = 'contact.unsubscribed';

    public const CONTACT_RESUBSCRIBED = 'contact.resubscribed';

    /**
     * A newsletter was sent. For an A/B newsletter, the instance sends this
     * event one time, when a caller chooses the winner.
     */
    public const NEWSLETTER_SENT = 'newsletter.sent';

    public const SEQUENCE_STEP_SENT = 'sequence_step.sent';

    public const SEQUENCE_COMPLETED = 'sequence.completed';

    public const TEST = 'webhook.test';

    /**
     * @param  string  $type  The event type, for example `contact.subscribed`.
     * @param  array<array-key, mixed>  $payload  The decoded JSON body.
     * @param  int  $timestamp  The Unix time when the instance signed the delivery.
     * @param  string|null  $deliveryId  The UUID of the delivery. Use it to ignore a delivery that you received before.
     */
    public function __construct(
        public string $type,
        public array $payload,
        public int $timestamp,
        public ?string $deliveryId = null,
        public ?DateTimeImmutable $occurredAt = null,
    ) {}

    /**
     * @param  array<array-key, mixed>  $payload
     */
    public static function fromDelivery(array $payload, int $timestamp, ?string $eventHeader = null, ?string $deliveryId = null): self
    {
        $type = $payload['event'] ?? $eventHeader;

        return new self(
            type: is_string($type) ? $type : '',
            payload: $payload,
            timestamp: $timestamp,
            deliveryId: $deliveryId,
            occurredAt: self::date($payload['occurred_at'] ?? null),
        );
    }

    public function is(string $type): bool
    {
        return $this->type === $type;
    }

    /**
     * The contact of a contact or a sequence event, with its custom attributes
     * at the top level. Null for the other events.
     *
     * @return array<array-key, mixed>|null
     */
    public function contact(): ?array
    {
        return is_array($this->payload['contact'] ?? null) ? $this->payload['contact'] : null;
    }

    /**
     * The decoded JSON body.
     *
     * @return array<array-key, mixed>
     */
    public function toArray(): array
    {
        return $this->payload;
    }

    private static function date(mixed $value): ?DateTimeImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Throwable) {
            return null;
        }
    }
}
