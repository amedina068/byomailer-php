<?php

declare(strict_types=1);

namespace Byomailer\Data;

use DateTimeImmutable;

/**
 * An automated series of emails.
 *
 * The list endpoint sends a summary. In a summary, `steps`, `filters`,
 * `contentVariables`, `segmentId` and `createdAt` are empty. Read one sequence
 * with `find()` to get them.
 */
final readonly class Sequence
{
    /**
     * @param  string  $status  One of `draft`, `live` or `paused`.
     * @param  list<SequenceStep>  $steps
     * @param  list<array<array-key, mixed>>  $filters
     * @param  array<string, string>  $contentVariables
     * @param  list<int>|null  $projectIds
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $status,
        public int $stepsCount = 0,
        public int $contactsCount = 0,
        public int $completedCount = 0,
        public int $activeCount = 0,
        public array $steps = [],
        public ?int $segmentId = null,
        public array $filters = [],
        public array $contentVariables = [],
        public ?array $projectIds = null,
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
            name: $payload->string('name'),
            status: $payload->string('status'),
            stepsCount: $payload->nullableInt('steps_count') ?? 0,
            contactsCount: $payload->nullableInt('contacts_count') ?? 0,
            completedCount: $payload->nullableInt('completed_count') ?? 0,
            activeCount: $payload->nullableInt('active_count') ?? 0,
            steps: array_map(SequenceStep::fromArray(...), $payload->objects('steps')),
            segmentId: $payload->nullableInt('segment_id'),
            filters: $payload->objects('filters'),
            contentVariables: $payload->stringMap('content_variables'),
            projectIds: $payload->nullableInts('project_ids'),
            createdAt: $payload->date('created_at'),
        );
    }

    public function isLive(): bool
    {
        return $this->status === 'live';
    }
}
