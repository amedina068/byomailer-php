<?php

declare(strict_types=1);

namespace Byomailer\Data;

use DateTimeImmutable;

/**
 * An automated series of emails, with its steps, its filters and its content
 * variables. The sequence list returns a `SequenceSummary` for each item.
 */
final readonly class Sequence
{
    /**
     * @param  string  $status  One of `draft`, `live` or `paused`.
     * @param  int  $completedCount  The contacts that finished the sequence.
     * @param  int  $activeCount  The contacts that did not finish the sequence.
     * @param  list<SequenceStep>  $steps
     * @param  list<array<array-key, mixed>>  $filters
     * @param  array<string, string>  $contentVariables
     * @param  list<int>|null  $projectIds
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $status,
        public int $stepsCount,
        public int $contactsCount,
        public int $completedCount,
        public int $activeCount,
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
            stepsCount: $payload->int('steps_count'),
            contactsCount: $payload->int('contacts_count'),
            completedCount: $payload->int('completed_count'),
            activeCount: $payload->int('active_count'),
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
