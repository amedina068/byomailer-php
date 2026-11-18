<?php

declare(strict_types=1);

/*
 * These tests call a real BYOMailer instance. They run only when
 * BYOMAILER_TEST_URL and BYOMAILER_TEST_TOKEN are set.
 *
 * The tests are read-only: they list contacts, lists and tags. They never
 * send email, never schedule a newsletter and never send a webhook test
 * delivery.
 */

use Byomailer\Byomailer;

function liveClient(): Byomailer
{
    return new Byomailer(
        url: (string) getenv('BYOMAILER_TEST_URL'),
        token: (string) getenv('BYOMAILER_TEST_TOKEN'),
        retries: 1,
    );
}

beforeEach(function () {
    if (! getenv('BYOMAILER_TEST_URL') || ! getenv('BYOMAILER_TEST_TOKEN')) {
        $this->markTestSkipped('Set BYOMAILER_TEST_URL and BYOMAILER_TEST_TOKEN to run the integration tests.');
    }
});

it('lists the contacts', function () {
    $page = liveClient()->contacts()->list(perPage: 5);

    expect(count($page))->toBeLessThanOrEqual(5);
})->group('integration');

it('lists the lists', function () {
    expect(liveClient()->lists()->list(perPage: 5)->items)->toBeArray();
})->group('integration');

it('lists the tags', function () {
    expect(liveClient()->tags()->list())->toBeArray();
})->group('integration');
