<?php

declare(strict_types=1);

namespace Byomailer\Data;

use DateTimeImmutable;

/**
 * One email of a sequence, sent after a delay.
 */
final readonly class SequenceStep
{
    /**
     * @param  list<array<array-key, mixed>>  $filters  A contact who does not match them skips only this step.
     * @param  array<array-key, mixed>|null  $message  The email: `subject`, `preview_text`, `html_content`, `text_content`, `email_identity_id`, `email_template_id`, `template_slot_content`, `email_template` and `email_identity`.
     */
    public function __construct(
        public int $id,
        public int $sequenceId,
        public string $status,
        public ?int $delayDays = null,
        public ?string $delayType = null,
        public array $filters = [],
        public ?array $message = null,
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
            sequenceId: $payload->int('sequence_id'),
            status: $payload->string('status'),
            delayDays: $payload->nullableInt('delay_days'),
            delayType: $payload->nullableString('delay_type'),
            filters: $payload->objects('filters'),
            message: $payload->nullableArray('message'),
            createdAt: $payload->date('created_at'),
        );
    }

    public function subject(): ?string
    {
        $subject = $this->message['subject'] ?? null;

        return is_string($subject) ? $subject : null;
    }
}
