<?php

declare(strict_types=1);

use Byomailer\Byomailer;
use Byomailer\Exceptions\MissingCredentialsException;
use Byomailer\Laravel\ByomailerServiceProvider;
use Byomailer\Laravel\Facades\Byomailer as ByomailerFacade;
use Byomailer\Resources\Contacts;
use Byomailer\Webhooks\Webhook;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

it('merges the package configuration', function () {
    expect(config('byomailer.timeout'))->toBe(30.0)
        ->and(config('byomailer.retries'))->toBe(2)
        ->and(config('byomailer.webhook.tolerance'))->toBe(300)
        ->and(config('byomailer.url'))->toBe('https://mail.example.com');
});

it('binds one client for the application', function () {
    $client = app(Byomailer::class);

    expect($client)->toBeInstanceOf(Byomailer::class)
        ->and(app('byomailer'))->toBe($client)
        ->and($client->url)->toBe('https://mail.example.com');
});

it('resolves the facade', function () {
    expect(ByomailerFacade::getFacadeRoot())->toBe(app(Byomailer::class))
        ->and(ByomailerFacade::contacts())->toBeInstanceOf(Contacts::class);
});

it('verifies a webhook from a Laravel request', function () {
    $case = json_decode((string) file_get_contents(__DIR__.'/../Fixtures/webhook-signature-vectors.json'), true)['cases'][2];

    $request = Request::create('/webhooks/byomailer', 'POST', server: [
        'HTTP_X_BYOMAILER_SIGNATURE' => $case['signature'],
        'HTTP_X_BYOMAILER_TIMESTAMP' => $case['timestamp'],
        'HTTP_X_BYOMAILER_EVENT' => 'newsletter.sent',
    ], content: $case['body']);

    $event = app(Webhook::class)->verify($request->getContent(), $request->headers->all(), now: $case['now']);

    expect($event->type)->toBe('newsletter.sent')
        ->and($event->payload['newsletter']['title'])->toBe('November news');
});

it('publishes the configuration file', function () {
    $paths = ServiceProvider::pathsToPublish(ByomailerServiceProvider::class, 'byomailer-config');

    expect($paths)->toHaveCount(1)
        ->and(array_key_first($paths))->toEndWith('config/byomailer.php')
        ->and(reset($paths))->toBe(config_path('byomailer.php'));
});

it('explains a missing instance URL', function () {
    config(['byomailer.url' => null]);
    app()->forgetInstance(Byomailer::class);

    app(Byomailer::class);
})->throws(MissingCredentialsException::class, 'BYOMAILER_URL');

it('explains a missing token', function () {
    config(['byomailer.token' => null]);
    app()->forgetInstance(Byomailer::class);

    app(Byomailer::class);
})->throws(MissingCredentialsException::class, 'BYOMAILER_TOKEN');

it('explains a missing webhook secret', function () {
    config(['byomailer.webhook.secret' => null]);
    app()->forgetInstance(Webhook::class);

    app(Webhook::class);
})->throws(MissingCredentialsException::class, 'BYOMAILER_WEBHOOK_SECRET');
