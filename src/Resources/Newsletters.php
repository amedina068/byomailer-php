<?php

declare(strict_types=1);

namespace Byomailer\Resources;

use Byomailer\Data\ContentVariables;
use Byomailer\Data\CursorPage;
use Byomailer\Data\Newsletter;
use Byomailer\Data\Payload;
use DateTimeInterface;
use Generator;

/**
 * The newsletters of your client.
 *
 * `create()` and `update()` take the API fields with their API names:
 * `title`, `subject`, `preview_text`, `html_content`, `status` (`draft` or
 * `live`), `scheduled_at`, `email_identity_id`, `email_template_id`,
 * `ab_test_enabled`, `subject_b`, `preview_text_b`, `html_content_b`,
 * `content_variables`, `filters` and `project_ids`.
 */
final readonly class Newsletters extends Resource
{
    /**
     * One page of newsletters, newest first. The list does not include the
     * HTML and the text content.
     *
     * @param  string|null  $status  One of `draft`, `scheduled`, `queued`, `ab_testing`, `sent` or `failed`.
     * @param  string|null  $search  Matches part of the title or the subject.
     * @param  int|null  $project  Only newsletters of this project.
     * @return CursorPage<Newsletter>
     */
    public function list(
        ?string $status = null,
        ?string $search = null,
        ?int $project = null,
        ?int $perPage = null,
        ?string $cursor = null,
    ): CursorPage {
        $body = $this->transport->get('newsletters', self::filled([
            'status' => $status,
            'search' => $search,
            'project' => $project,
            'per_page' => $perPage,
            'cursor' => $cursor,
        ]))->json();

        return CursorPage::fromBody($body, Newsletter::fromArray(...));
    }

    /**
     * @return Generator<int, Newsletter>
     */
    public function all(?string $status = null, ?string $search = null, ?int $project = null, ?int $perPage = null): Generator
    {
        return CursorPage::walk(fn (?string $cursor): CursorPage => $this->list($status, $search, $project, $perPage, $cursor));
    }

    public function find(int $newsletterId): Newsletter
    {
        return Newsletter::fromArray($this->transport->get("newsletters/{$newsletterId}")->data());
    }

    /**
     * Create a newsletter. With no `status`, the newsletter is a draft.
     *
     * A `live` status with a `scheduled_at` in the future schedules the
     * newsletter. A `live` status with a `scheduled_at` of now sends it.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes = []): Newsletter
    {
        return Newsletter::fromArray($this->transport->post('newsletters', self::body($attributes))->data());
    }

    /**
     * Change a draft or a scheduled newsletter. A field that you do not give
     * keeps its value. The instance refuses a change to a newsletter that is
     * sending or sent.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(int $newsletterId, array $attributes): Newsletter
    {
        return Newsletter::fromArray($this->transport->patch("newsletters/{$newsletterId}", self::body($attributes))->data());
    }

    /**
     * Delete a draft newsletter. The instance refuses to delete a newsletter
     * that is not a draft.
     */
    public function delete(int $newsletterId): void
    {
        $this->transport->delete("newsletters/{$newsletterId}");
    }

    /**
     * The number of contacts that match the audience filters. The instance
     * allows 30 requests a minute.
     *
     * @param  list<array<string, mixed>>  $filters  An empty list counts each subscribed contact.
     */
    public function countRecipients(array $filters = []): int
    {
        $body = $this->transport->post('newsletters/contacts/preview', ['filters' => $filters], idempotent: true)->json();

        return Payload::of($body)->int('count');
    }

    /**
     * Send the newsletter to the email address of the token user. The
     * instance allows 5 requests a minute.
     *
     * @return string The address that receives the test email.
     */
    public function sendTest(int $newsletterId): string
    {
        return Payload::of($this->transport->post("newsletters/{$newsletterId}/test-mail")->json())->string('sent_to');
    }

    /**
     * Choose the winner of an A/B test. The newsletter must have the
     * `ab_testing` status. Each half of the audience already got one variant,
     * so the instance sends no more email. The newsletter gets the `sent`
     * status, and the instance sends the `newsletter.sent` webhook event one time.
     *
     * @param  string  $variant  `a` or `b`.
     */
    public function pickWinner(int $newsletterId, string $variant): Newsletter
    {
        return Newsletter::fromArray(
            $this->transport->post("newsletters/{$newsletterId}/ab-winner", ['winner_variant' => $variant])->data()
        );
    }

    public function variables(int $newsletterId): ContentVariables
    {
        return ContentVariables::fromArray($this->transport->get("newsletters/{$newsletterId}/variables")->data());
    }

    /**
     * Replace all the saved defaults of a draft or a scheduled newsletter.
     * An empty array removes all the defaults.
     *
     * @param  array<string, string|null>  $defaults  The default value of each variable, for example `['first_name' => 'friend']`.
     */
    public function replaceVariables(int $newsletterId, array $defaults): ContentVariables
    {
        $response = $this->transport->patch("newsletters/{$newsletterId}/variables", [
            'content_variables' => (object) $defaults,
        ], idempotent: true);

        return ContentVariables::fromArray($response->data());
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private static function body(array $attributes): array
    {
        if (($attributes['scheduled_at'] ?? null) instanceof DateTimeInterface) {
            $attributes['scheduled_at'] = $attributes['scheduled_at']->format(DateTimeInterface::ATOM);
        }

        if (isset($attributes['content_variables']) && is_array($attributes['content_variables'])) {
            $attributes['content_variables'] = (object) $attributes['content_variables'];
        }

        return $attributes;
    }
}
