<?php

declare(strict_types=1);

namespace Byomailer\Data;

use DateTimeImmutable;

/**
 * One attempt by the instance to send an event to a webhook endpoint.
 */
final readonly class WebhookDelivery
{
    /**
     * @param  string  $status  For example `pending`, `success` or `failed`.
     * @param  array<array-key, mixed>  $payload  The JSON body that the instance sends.
     */
    public function __construct(
        public int $id,
        public string $uuid,
        public string $eventType,
        public string $status,
        public int $attempts = 0,
        public ?int $responseStatus = null,
        public ?string $responseBody = null,
        public ?string $error = null,
        public array $payload = [],
        public ?DateTimeImmutable $dispatchedAt = null,
        public ?DateTimeImmutable $deliveredAt = null,
        public ?DateTimeImmutable $createdAt = null,
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
            eventType: $payload->string('event_type'),
            status: $payload->string('status'),
            attempts: $payload->nullableInt('attempts') ?? 0,
            responseStatus: $payload->nullableInt('response_status'),
            responseBody: $payload->nullableString('response_body'),
            error: $payload->nullableString('error'),
            payload: $payload->array('payload'),
            dispatchedAt: $payload->date('dispatched_at'),
            deliveredAt: $payload->date('delivered_at'),
            createdAt: $payload->date('created_at'),
        );
    }
}
