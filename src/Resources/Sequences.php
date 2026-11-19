<?php

declare(strict_types=1);

namespace Byomailer\Resources;

use Byomailer\Data\ContentVariables;
use Byomailer\Data\CursorPage;
use Byomailer\Data\Sequence;
use Byomailer\Data\SequenceStats;
use Byomailer\Data\SequenceStep;
use Byomailer\Data\SequenceSummary;
use Byomailer\Enums\SequenceStatus;
use DateTimeInterface;
use Generator;

/**
 * The sequences of your client. The API reads sequences and changes only
 * their content variables and the filters of their steps.
 */
final readonly class Sequences extends Resource
{
    /**
     * One page of sequence summaries. Live sequences come first, then the
     * others by name.
     *
     * @param  string|null  $search  Matches part of the name.
     * @param  int|null  $project  Only sequences of this project.
     * @return CursorPage<SequenceSummary>
     */
    public function list(
        ?SequenceStatus $status = null,
        ?string $search = null,
        ?int $project = null,
        ?int $perPage = null,
        ?string $cursor = null,
    ): CursorPage {
        $body = $this->transport->get('sequences', self::filled([
            'status' => $status?->value,
            'search' => $search,
            'project' => $project,
            'per_page' => $perPage,
            'cursor' => $cursor,
        ]))->json();

        return CursorPage::fromBody($body, SequenceSummary::fromArray(...));
    }

    /**
     * @return Generator<int, SequenceSummary>
     */
    public function all(?SequenceStatus $status = null, ?string $search = null, ?int $project = null, ?int $perPage = null): Generator
    {
        return CursorPage::walk(fn (?string $cursor): CursorPage => $this->list($status, $search, $project, $perPage, $cursor));
    }

    /**
     * One sequence, with its steps and its filters.
     */
    public function find(int $sequenceId): Sequence
    {
        return Sequence::fromArray($this->transport->get("sequences/{$sequenceId}")->data());
    }

    public function stats(int $sequenceId): SequenceStats
    {
        return SequenceStats::fromArray($this->transport->get("sequences/{$sequenceId}/stats")->data());
    }

    public function step(int $sequenceId, int $stepId): SequenceStep
    {
        return SequenceStep::fromArray($this->transport->get("sequences/{$sequenceId}/steps/{$stepId}")->data());
    }

    /**
     * The statistics of the contacts whose furthest progress is this step.
     */
    public function stepStats(int $sequenceId, int $stepId): SequenceStats
    {
        return SequenceStats::fromArray($this->transport->get("sequences/{$sequenceId}/steps/{$stepId}/stats")->data());
    }

    /**
     * Replace the audience filters of one step. A contact who does not match
     * them skips only this step. An empty list removes the filters.
     *
     * @param  list<array<string, mixed>>  $filters
     */
    public function replaceStepFilters(int $sequenceId, int $stepId, array $filters): SequenceStep
    {
        $response = $this->transport->patch(
            "sequences/{$sequenceId}/steps/{$stepId}/filters",
            ['filters' => $filters],
            idempotent: true,
        );

        return SequenceStep::fromArray($response->data());
    }

    public function variables(int $sequenceId): ContentVariables
    {
        return ContentVariables::fromArray($this->transport->get("sequences/{$sequenceId}/variables")->data());
    }

    /**
     * Replace all the saved defaults of the sequence. An empty array removes
     * all the defaults.
     *
     * @param  array<string, string|null>  $defaults  The default value of each variable.
     */
    public function replaceVariables(int $sequenceId, array $defaults): ContentVariables
    {
        $response = $this->transport->patch("sequences/{$sequenceId}/variables", [
            'content_variables' => (object) $defaults,
        ], idempotent: true);

        return ContentVariables::fromArray($response->data());
    }

    /**
     * The mails of one step as CSV text, with a header row. The export always
     * has the full history of the step.
     *
     * The SDK does not send `current_only`. The instance ignores this
     * deprecated filter.
     *
     * @param  array<string, string|int|bool|DateTimeInterface>  $filters  The query filters: `contact_id`, `status`, `filter`, `search`, `sent_after`, `sent_before`, `sort` and `direction`.
     */
    public function exportStepMails(int $sequenceId, int $stepId, array $filters = []): string
    {
        unset($filters['current_only']);

        $query = array_map(static fn (mixed $value): mixed => match (true) {
            $value instanceof DateTimeInterface => $value->format(DateTimeInterface::ATOM),
            is_bool($value) => (int) $value,
            default => $value,
        }, $filters);

        return $this->transport->get("sequences/{$sequenceId}/steps/{$stepId}/contact-mails/export", $query)->body;
    }
}
