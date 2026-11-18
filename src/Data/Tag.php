<?php

declare(strict_types=1);

namespace Byomailer\Data;

use DateTimeImmutable;

/**
 * A label for contacts.
 */
final readonly class Tag
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $name,
        public ?int $contactsCount = null,
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
            name: $payload->string('name'),
            contactsCount: $payload->nullableInt('contacts_count'),
            createdAt: $payload->date('created_at'),
        );
    }
}
