<?php

declare(strict_types=1);

namespace Byomailer\Data;

use DateTimeImmutable;

/**
 * An HTML layout that wraps the content of a newsletter or a sequence step.
 */
final readonly class EmailTemplate
{
    public function __construct(
        public int $id,
        public string $name,
        public string $htmlContent,
        public bool $isDefault = false,
        public ?DateTimeImmutable $archivedAt = null,
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
            name: $payload->string('name'),
            htmlContent: $payload->nullableString('html_content') ?? '',
            isDefault: $payload->bool('is_default'),
            archivedAt: $payload->date('archived_at'),
            createdAt: $payload->date('created_at'),
            updatedAt: $payload->date('updated_at'),
        );
    }
}
