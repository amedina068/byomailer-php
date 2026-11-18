<?php

declare(strict_types=1);

namespace Byomailer\Data;

use DateTimeImmutable;

/**
 * A sender address or domain in the Amazon SES account of the instance.
 */
final readonly class EmailIdentity
{
    /**
     * @param  string|null  $status  The SES verification status, for example `verified` or `pending`.
     */
    public function __construct(
        public int $id,
        public string $uuid,
        public string $email,
        public ?string $name = null,
        public ?string $domain = null,
        public ?string $status = null,
        public ?DateTimeImmutable $verifiedAt = null,
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $updatedAt = null,
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
            email: $payload->string('email'),
            name: $payload->nullableString('name'),
            domain: $payload->nullableString('domain'),
            status: $payload->nullableString('identity_status'),
            verifiedAt: $payload->date('verified_at'),
            createdAt: $payload->date('created_at'),
            updatedAt: $payload->date('updated_at'),
        );
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }
}
