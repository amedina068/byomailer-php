<?php

declare(strict_types=1);

use Byomailer\Byomailer;
use Byomailer\Exceptions\MissingCredentialsException;
use GuzzleHttp\Client;

it('sends the token and the JSON headers to the versioned API', function () {
    $history = [];

    byomailer([json(['data' => []])], $history)->tags()->list();

    $request = $history[0]['request'];

    expect((string) $request->getUri())->toBe('https://mail.example.com/api/v1/tags')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer test-token')
        ->and($request->getHeaderLine('Accept'))->toBe('application/json')
        ->and($request->getHeaderLine('User-Agent'))->toBe('byomailer-php/'.Byomailer::VERSION);
});

it('sends a JSON body with the content type', function () {
    $history = [];

    byomailer([json(['data' => tagPayload()], 201)], $history)->tags()->create('vip');

    expect($history[0]['request']->getHeaderLine('Content-Type'))->toBe('application/json')
        ->and(body($history[0]['request']))->toBe(['name' => 'vip']);
});

it('normalizes the instance URL', function (string $given) {
    expect((new Byomailer($given, 'token'))->url)->toBe('https://mail.example.com');
})->with([
    'plain' => 'https://mail.example.com',
    'trailing slash' => 'https://mail.example.com/',
    'api suffix' => 'https://mail.example.com/api',
    'api v1 suffix' => ' https://mail.example.com/api/v1/ ',
]);

it('needs an instance URL', function () {
    new Byomailer('', 'token');
})->throws(MissingCredentialsException::class, 'BYOMAILER_URL');

it('needs a token', function () {
    new Byomailer('https://mail.example.com', ' ');
})->throws(MissingCredentialsException::class, 'BYOMAILER_TOKEN');

it('keeps the token out of debug output', function () {
    $client = new Byomailer('https://mail.example.com', 'secret-token-value');

    expect(print_r($client, true))->not->toContain('secret-token-value');
});

it('creates a Guzzle client with the timeout when no client is given', function () {
    $client = new Byomailer('https://mail.example.com', 'token', timeout: 12.5);

    $transport = (fn () => $this->transport)->call($client);
    $http = (fn () => $this->client)->call($transport);

    expect($http)->toBeInstanceOf(Client::class)
        ->and((fn () => $this->config['timeout'])->call($http))->toBe(12.5);
});
