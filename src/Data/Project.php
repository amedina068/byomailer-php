<?php

declare(strict_types=1);

namespace Byomailer\Data;

use DateTimeImmutable;

/**
 * A group of newsletters, sequences and automations.
 *
 * The counts are null when the endpoint did not send them. The create and the
 * rename endpoints do not send them.
 */
final readonly class Project
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $name,
        public ?int $newslettersCount = null,
        public ?int $sequencesCount = null,
        public ?int $automationsCount = null,
        public ?int $visualAutomationsCount = null,
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
            newslettersCount: $payload->nullableInt('newsletters_count'),
            sequencesCount: $payload->nullableInt('sequences_count'),
            automationsCount: $payload->nullableInt('automations_count'),
            visualAutomationsCount: $payload->nullableInt('visual_automations_count'),
            createdAt: $payload->date('created_at'),
        );
    }
}
