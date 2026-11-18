<?php

declare(strict_types=1);

namespace Byomailer\Data;

use DateTimeImmutable;

/**
 * A one-time email to the contacts that match its filters.
 *
 * The list endpoint does not send the content. In a list, the four content
 * fields are null. Read one newsletter with `find()` to get them.
 */
final readonly class Newsletter
{
    /**
     * @param  string  $status  One of `draft`, `scheduled`, `queued`, `ab_testing`, `sent` or `failed`.
     * @param  list<array<array-key, mixed>>  $filters  The audience filters.
     * @param  array<string, string>  $contentVariables  The default value of each `{{ variable }}`.
     * @param  list<int>|null  $projectIds
     * @param  array{total: int, sent: int, pending: int, opened: int, bounced: int, unsubscribed: int, complained: int, open_rate: float, unsubscribe_rate: float}  $metrics
     */
    public function __construct(
        public int $id,
        public string $uuid,
        public string $title,
        public string $status,
        public ?string $subject = null,
        public ?string $previewText = null,
        public ?int $emailIdentityId = null,
        public ?EmailIdentity $emailIdentity = null,
        public ?int $emailTemplateId = null,
        public ?string $fromEmail = null,
        public ?string $fromName = null,
        public ?DateTimeImmutable $scheduledAt = null,
        public ?DateTimeImmutable $sentAt = null,
        public ?int $segmentId = null,
        public array $filters = [],
        public array $contentVariables = [],
        public bool $abTestEnabled = false,
        public ?string $abTestWinnerVariant = null,
        public ?string $subjectB = null,
        public ?string $previewTextB = null,
        public int $totalContactsReached = 0,
        public ?array $projectIds = null,
        public array $metrics = ['total' => 0, 'sent' => 0, 'pending' => 0, 'opened' => 0, 'bounced' => 0, 'unsubscribed' => 0, 'complained' => 0, 'open_rate' => 0.0, 'unsubscribe_rate' => 0.0],
        public ?string $htmlContent = null,
        public ?string $textContent = null,
        public ?string $htmlContentB = null,
        public ?string $textContentB = null,
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $updatedAt = null,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $payload = Payload::of($data);
        $identity = $payload->nullableArray('email_identity');

        return new self(
            id: $payload->int('id'),
            uuid: $payload->string('uuid'),
            title: $payload->string('title'),
            status: $payload->string('status'),
            subject: $payload->nullableString('subject'),
            previewText: $payload->nullableString('preview_text'),
            emailIdentityId: $payload->nullableInt('email_identity_id'),
            emailIdentity: $identity === null ? null : EmailIdentity::fromArray($identity),
            emailTemplateId: $payload->nullableInt('email_template_id'),
            fromEmail: $payload->nullableString('from_email'),
            fromName: $payload->nullableString('from_name'),
            scheduledAt: $payload->date('scheduled_at'),
            sentAt: $payload->date('sent_at'),
            segmentId: $payload->nullableInt('segment_id'),
            filters: $payload->objects('filters'),
            contentVariables: $payload->stringMap('content_variables'),
            abTestEnabled: $payload->bool('ab_test_enabled'),
            abTestWinnerVariant: $payload->nullableString('ab_test_winner_variant'),
            subjectB: $payload->nullableString('subject_b'),
            previewTextB: $payload->nullableString('preview_text_b'),
            totalContactsReached: $payload->nullableInt('total_contacts_reached') ?? 0,
            projectIds: $payload->nullableInts('project_ids'),
            metrics: self::metrics(Payload::of($payload->array('metrics'))),
            htmlContent: $payload->nullableString('html_content'),
            textContent: $payload->nullableString('text_content'),
            htmlContentB: $payload->nullableString('html_content_b'),
            textContentB: $payload->nullableString('text_content_b'),
            createdAt: $payload->date('created_at'),
            updatedAt: $payload->date('updated_at'),
        );
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * True when the API accepts changes to the newsletter.
     */
    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'scheduled'], true);
    }

    /**
     * @return array{total: int, sent: int, pending: int, opened: int, bounced: int, unsubscribed: int, complained: int, open_rate: float, unsubscribe_rate: float}
     */
    private static function metrics(Payload $metrics): array
    {
        $count = static fn (string $key): int => $metrics->nullableInt($key) ?? 0;
        $rate = static fn (string $key): float => (float) ($metrics->nullableString($key) ?? 0);

        return [
            'total' => $count('total'),
            'sent' => $count('sent'),
            'pending' => $count('pending'),
            'opened' => $count('opened'),
            'bounced' => $count('bounced'),
            'unsubscribed' => $count('unsubscribed'),
            'complained' => $count('complained'),
            'open_rate' => $rate('open_rate'),
            'unsubscribe_rate' => $rate('unsubscribe_rate'),
        ];
    }
}
