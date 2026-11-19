<?php

declare(strict_types=1);

namespace Byomailer\Data;

/**
 * One item of the sequence list. Read one sequence with `find()` to get its
 * steps, its filters and its content variables.
 */
final readonly class SequenceSummary
{
    /**
     * @param  string  $status  One of `draft`, `live` or `paused`.
     * @param  int  $completedCount  The contacts that finished the sequence.
     * @param  int  $activeCount  The contacts that did not finish the sequence.
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
        public ?array $projectIds = null,
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
            projectIds: $payload->nullableInts('project_ids'),
        );
    }

    public function isLive(): bool
    {
        return $this->status === 'live';
    }
}
