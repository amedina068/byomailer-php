<?php

declare(strict_types=1);

namespace Byomailer\Data;

use DateTimeImmutable;

/**
 * One record of a call to Amazon SES or of a notification from it.
 */
final readonly class EmailLog
{
    /**
     * @param  array<array-key, mixed>|null  $requestPayload
     * @param  array<array-key, mixed>|null  $responsePayload
     * @param  array<array-key, mixed>|null  $snsPayload
     */
    public function __construct(
        public int $id,
        public string $provider,
        public string $operation,
        public string $status,
        public ?string $recipient = null,
        public ?string $messageId = null,
        public ?string $error = null,
        public ?array $requestPayload = null,
        public ?array $responsePayload = null,
        public ?array $snsPayload = null,
        public ?DateTimeImmutable $loggedAt = null,
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
            provider: $payload->string('provider'),
            operation: $payload->string('operation'),
            status: $payload->string('status'),
            recipient: $payload->nullableString('recipient'),
            messageId: $payload->nullableString('message_id'),
            error: $payload->nullableString('error'),
            requestPayload: $payload->nullableArray('request_payload'),
            responsePayload: $payload->nullableArray('response_payload'),
            snsPayload: $payload->nullableArray('sns_payload'),
            loggedAt: $payload->date('logged_at'),
            createdAt: $payload->date('created_at'),
        );
    }
}
