<?php

declare(strict_types=1);

use Byomailer\Exceptions\ApiException;
use Byomailer\Exceptions\AuthenticationException;
use Byomailer\Exceptions\AuthorizationException;
use Byomailer\Exceptions\ByomailerException;
use Byomailer\Exceptions\ConnectionException;
use Byomailer\Exceptions\NotFoundException;
use Byomailer\Exceptions\RateLimitException;
use Byomailer\Exceptions\ServerException;
use Byomailer\Exceptions\UnexpectedResponseException;
use Byomailer\Exceptions\ValidationException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

it('throws a typed exception for each error status', function (int $status, string $class) {
    try {
        byomailer([json(['message' => 'Nope.'], $status)])->tags()->list();
    } catch (ApiException $exception) {
        expect($exception)->toBeInstanceOf($class)
            ->toBeInstanceOf(ByomailerException::class)
            ->and($exception->status)->toBe($status)
            ->and($exception->getMessage())->toBe('Nope.');

        return;
    }

    test()->fail('No exception was thrown.');
})->with([
    'authentication' => [401, AuthenticationException::class],
    'authorization' => [403, AuthorizationException::class],
    'not found' => [404, NotFoundException::class],
    'validation' => [422, ValidationException::class],
    'rate limit' => [429, RateLimitException::class],
    'server' => [500, ServerException::class],
    'bad gateway' => [502, ServerException::class],
    'other 4xx' => [405, ApiException::class],
]);

it('gives the field errors of a validation error', function () {
    try {
        byomailer([json([
            'message' => 'The email field must be a valid email address.',
            'errors' => ['email' => ['The email field must be a valid email address.'], 'tags.0' => ['Too long.']],
        ], 422)])->contacts()->addToList(3, 'not-an-email');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toBe([
            'email' => ['The email field must be a valid email address.'],
            'tags.0' => ['Too long.'],
        ])
            ->and($exception->first('email'))->toBe('The email field must be a valid email address.')
            ->and($exception->first('name'))->toBeNull()
            ->and($exception->has('email'))->toBeTrue()
            ->and($exception->has('name'))->toBeFalse();

        return;
    }

    test()->fail('No exception was thrown.');
});

it('gives the Retry-After value of a rate limit error', function () {
    try {
        byomailer([json(['message' => 'Too Many Attempts.'], 429, ['Retry-After' => '42'])])->newsletters()->sendTest(7);
    } catch (RateLimitException $exception) {
        expect($exception->retryAfter())->toBe(42);

        return;
    }

    test()->fail('No exception was thrown.');
});

it('explains a 403 from a read-only token', function () {
    expect(fn () => byomailer([json(['message' => 'You have read-only access.'], 403)])->tags()->create('vip'))
        ->toThrow(AuthorizationException::class, 'You have read-only access.');
});

it('uses a default message when the error body is not JSON', function () {
    expect(fn () => byomailer([new Response(500, [], '<html>Server Error</html>')])->tags()->list())
        ->toThrow(ServerException::class, 'The BYOMailer instance answered with HTTP status 500.');
});

it('throws a connection exception', function () {
    $error = new ConnectException('cURL error 6: Could not resolve host: mail.example.com', new Request('GET', 'https://mail.example.com'));

    try {
        byomailer([$error])->tags()->list();
    } catch (ConnectionException $exception) {
        expect($exception->requestSent)->toBeFalse()
            ->and($exception->getPrevious())->toBe($error)
            ->and($exception->getMessage())->toContain('Could not resolve host');

        return;
    }

    test()->fail('No exception was thrown.');
});

it('throws an unexpected response exception for a body that is not JSON', function () {
    expect(fn () => byomailer([new Response(200, [], 'not json')])->tags()->list())
        ->toThrow(UnexpectedResponseException::class, 'not a JSON object');
});

it('throws an unexpected response exception for a missing field', function () {
    expect(fn () => byomailer([json(['data' => ['id' => 1]])])->tags()->rename(1, 'x'))
        ->toThrow(UnexpectedResponseException::class, '[uuid]');
});
