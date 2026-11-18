<?php

declare(strict_types=1);

namespace Byomailer\Data;

/**
 * The statistics of a sequence, or of one step of a sequence.
 *
 * The step fields (`sequenceId`, `subject`, `delayDays` and `delayType`) are
 * null for the statistics of a whole sequence. The `name` is null for a step.
 */
final readonly class SequenceStats
{
    /**
     * @param  array{contacts: int, unsubscribed: int, clicked: int}  $stats
     * @param  array<string, int|float|null>  $mailMetrics  The mail counts (`total`, `sent`, `delivered`, `pending`, `skipped`, `opened`, `bounced`, `unsubscribed`, `complained`) and the rates (`delivery_rate`, `open_rate`, `unsubscribe_rate`).
     */
    public function __construct(
        public int $id,
        public string $status,
        public array $stats,
        public array $mailMetrics,
        public ?string $name = null,
        public ?int $sequenceId = null,
        public ?string $subject = null,
        public ?int $delayDays = null,
        public ?string $delayType = null,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $payload = Payload::of($data);
        $stats = Payload::of($payload->array('stats'));

        return new self(
            id: $payload->int('id'),
            status: $payload->string('status'),
            stats: [
                'contacts' => $stats->nullableInt('contacts') ?? 0,
                'unsubscribed' => $stats->nullableInt('unsubscribed') ?? 0,
                'clicked' => $stats->nullableInt('clicked') ?? 0,
            ],
            mailMetrics: self::metrics($payload->array('mail_metrics')),
            name: $payload->nullableString('name'),
            sequenceId: $payload->nullableInt('sequence_id'),
            subject: $payload->nullableString('subject'),
            delayDays: $payload->nullableInt('delay_days'),
            delayType: $payload->nullableString('delay_type'),
        );
    }

    /**
     * @param  array<array-key, mixed>  $metrics
     * @return array<string, int|float|null>
     */
    private static function metrics(array $metrics): array
    {
        $result = [];

        foreach ($metrics as $name => $value) {
            $result[(string) $name] = is_int($value) || is_float($value) ? $value : null;
        }

        return $result;
    }
}
