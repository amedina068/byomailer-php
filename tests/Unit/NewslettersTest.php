<?php

declare(strict_types=1);

use Byomailer\Data\EmailIdentity;

it('lists newsletters with the filters', function () {
    $history = [];
    $summary = newsletterPayload();
    unset($summary['html_content'], $summary['text_content'], $summary['html_content_b'], $summary['text_content_b']);

    $page = byomailer([json(page([$summary]))], $history)->newsletters()->list(status: 'draft', search: 'November', project: 1, perPage: 5);

    expect(sent($history[0]['request']))->toBe('GET /api/v1/newsletters?status=draft&search=November&project=1&per_page=5')
        ->and($page->first()?->htmlContent)->toBeNull()
        ->and($page->first()?->title)->toBe('November news');
});

it('reads a newsletter', function () {
    $newsletter = byomailer([json(['data' => newsletterPayload()])])->newsletters()->find(7);

    expect($newsletter->id)->toBe(7)
        ->and($newsletter->isDraft())->toBeTrue()
        ->and($newsletter->isEditable())->toBeTrue()
        ->and($newsletter->emailIdentity)->toBeInstanceOf(EmailIdentity::class)
        ->and($newsletter->emailIdentity?->isVerified())->toBeTrue()
        ->and($newsletter->contentVariables)->toBe(['first_name' => 'friend'])
        ->and($newsletter->filters)->toBe([['category' => 'lists', 'mode' => 'any', 'values' => [3]]])
        ->and($newsletter->projectIds)->toBe([1])
        ->and($newsletter->metrics['open_rate'])->toBe(50.0)
        ->and($newsletter->metrics['unsubscribe_rate'])->toBe(12.5)
        ->and($newsletter->htmlContent)->toBe('<p>Hello {{ first_name }}</p>');
});

it('reads an empty content variable map', function () {
    $newsletter = byomailer([json(['data' => newsletterPayload(['content_variables' => [], 'email_identity' => null])])])->newsletters()->find(7);

    expect($newsletter->contentVariables)->toBe([])
        ->and($newsletter->emailIdentity)->toBeNull();
});

it('creates a draft newsletter', function () {
    $history = [];

    byomailer([json(['data' => newsletterPayload()], 201)], $history)->newsletters()->create([
        'title' => 'November news',
        'subject' => 'Our November news',
        'html_content' => '<p>Hello</p>',
        'content_variables' => ['first_name' => 'friend'],
    ]);

    expect(sent($history[0]['request']))->toBe('POST /api/v1/newsletters')
        ->and((string) $history[0]['request']->getBody())->toContain('"content_variables":{"first_name":"friend"}');
});

it('sends a schedule date in ISO 8601', function () {
    $history = [];

    byomailer([json(['data' => newsletterPayload(['status' => 'scheduled'])])], $history)->newsletters()->update(7, [
        'status' => 'live',
        'scheduled_at' => new DateTimeImmutable('2026-12-01 09:00:00', new DateTimeZone('UTC')),
    ]);

    expect(sent($history[0]['request']))->toBe('PATCH /api/v1/newsletters/7')
        ->and(body($history[0]['request']))->toBe(['status' => 'live', 'scheduled_at' => '2026-12-01T09:00:00+00:00']);
});

it('deletes a newsletter', function () {
    $history = [];

    byomailer([noContent()], $history)->newsletters()->delete(7);

    expect(sent($history[0]['request']))->toBe('DELETE /api/v1/newsletters/7');
});

it('counts the recipients of the filters', function () {
    $history = [];
    $filters = [['category' => 'tags', 'mode' => 'any', 'values' => [5]]];

    $count = byomailer([json(['count' => 128])], $history)->newsletters()->countRecipients($filters);

    expect($count)->toBe(128)
        ->and(sent($history[0]['request']))->toBe('POST /api/v1/newsletters/contacts/preview')
        ->and(body($history[0]['request']))->toBe(['filters' => $filters]);
});

it('sends a test email', function () {
    $history = [];

    $address = byomailer([json(['message' => 'Test email sent to owner@example.com', 'sent_to' => 'owner@example.com'])], $history)
        ->newsletters()->sendTest(7);

    expect($address)->toBe('owner@example.com')
        ->and(sent($history[0]['request']))->toBe('POST /api/v1/newsletters/7/test-mail');
});

it('chooses the A/B winner', function () {
    $history = [];

    $newsletter = byomailer([json(['data' => newsletterPayload(['status' => 'sent', 'ab_test_enabled' => true, 'ab_test_winner_variant' => 'b'])])], $history)
        ->newsletters()->chooseWinner(7, 'b');

    expect(sent($history[0]['request']))->toBe('POST /api/v1/newsletters/7/ab-winner')
        ->and(body($history[0]['request']))->toBe(['winner_variant' => 'b'])
        ->and($newsletter->abTestWinnerVariant)->toBe('b');
});

it('reads the content variables', function () {
    $history = [];

    $variables = byomailer([json(['data' => [
        'newsletter_id' => 7,
        'detected_variables' => ['first_name', 'coupon'],
        'content_variables' => ['first_name' => 'friend', 'coupon' => ''],
    ]])], $history)->newsletters()->variables(7);

    expect(sent($history[0]['request']))->toBe('GET /api/v1/newsletters/7/variables')
        ->and($variables->detected)->toBe(['first_name', 'coupon'])
        ->and($variables->default('first_name'))->toBe('friend')
        ->and($variables->missing())->toBe(['coupon']);
});

it('replaces the content variables with a JSON object', function () {
    $history = [];

    byomailer([json(['data' => ['newsletter_id' => 7, 'detected_variables' => [], 'content_variables' => []]])], $history)
        ->newsletters()->replaceVariables(7, []);

    expect(sent($history[0]['request']))->toBe('PATCH /api/v1/newsletters/7/variables')
        ->and((string) $history[0]['request']->getBody())->toBe('{"content_variables":{}}');
});
