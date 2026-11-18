<?php

declare(strict_types=1);

namespace Byomailer\Data;

use DateTimeImmutable;

/**
 * A mailing list. Contacts subscribe to lists, and newsletters filter by list.
 */
final readonly class MailList
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $name,
        public bool $optInEnabled = false,
        public ?int $contactsCount = null,
        public ?int $topicsCount = null,
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
            optInEnabled: $payload->bool('opt_in_enabled'),
            contactsCount: $payload->nullableInt('contacts_count'),
            topicsCount: $payload->nullableInt('topics_count'),
            createdAt: $payload->date('created_at'),
        );
    }
}
