<?php

declare(strict_types=1);

namespace Byomailer\Data;

use DateTimeImmutable;

/**
 * A reusable piece of HTML. Content inserts it by its name.
 */
final readonly class Block
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $name,
        public string $htmlContent,
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
            name: $payload->string('name'),
            htmlContent: $payload->string('html_content'),
            createdAt: $payload->date('created_at'),
            updatedAt: $payload->date('updated_at'),
        );
    }
}
