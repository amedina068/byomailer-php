<?php

declare(strict_types=1);

namespace Byomailer\Data;

use DateTimeImmutable;

/**
 * A subject inside a list. A contact can unsubscribe from one topic and stay
 * on the list.
 */
final readonly class Topic
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $listId,
        public string $name,
        public ?string $description = null,
        public ?string $listName = null,
        public ?int $subscribedCount = null,
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
            listId: $payload->int('list_id'),
            name: $payload->string('name'),
            description: $payload->nullableString('description'),
            listName: $payload->nullableString('list_name'),
            subscribedCount: $payload->nullableInt('subscribed_count'),
            createdAt: $payload->date('created_at'),
        );
    }
}
