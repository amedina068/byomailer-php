<?php

declare(strict_types=1);

namespace Byomailer;

use Byomailer\Exceptions\MissingCredentialsException;
use Byomailer\Http\HttpClientFactory;
use Byomailer\Http\RetryPolicy;
use Byomailer\Http\Transport;
use Byomailer\Resources\Blocks;
use Byomailer\Resources\Contacts;
use Byomailer\Resources\EmailIdentities;
use Byomailer\Resources\EmailLogs;
use Byomailer\Resources\EmailTemplates;
use Byomailer\Resources\Lists;
use Byomailer\Resources\Newsletters;
use Byomailer\Resources\Projects;
use Byomailer\Resources\Sequences;
use Byomailer\Resources\Tags;
use Byomailer\Resources\Topics;
use Byomailer\Resources\WebhookEndpoints;
use Http\Discovery\Psr17FactoryDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use SensitiveParameter;

/**
 * The client for one BYOMailer instance.
 *
 *     $byomailer = new Byomailer('https://mail.example.com', $token);
 *
 *     $contact = $byomailer->contacts()->add(3, 'ana@example.com', name: 'Ana');
 */
class Byomailer
{
    public const VERSION = '0.2.0';

    /**
     * The URL of the instance, with no trailing slash and no `/api` suffix.
     */
    public readonly string $url;

    protected readonly Transport $transport;

    /**
     * @param  string  $url  The URL of your instance, for example `https://mail.example.com`.
     * @param  string  $token  A personal API token. Make it in Settings, API Tokens.
     * @param  float  $timeout  The request timeout in seconds. The SDK uses it when it creates the HTTP client.
     * @param  int|RetryPolicy  $retries  The number of retries, or a full retry policy.
     * @param  ClientInterface|null  $httpClient  Any PSR-18 client. If you give one, configure its timeout yourself.
     */
    public function __construct(
        string $url,
        #[SensitiveParameter] string $token,
        float $timeout = 30.0,
        int|RetryPolicy $retries = 2,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ) {
        $this->url = self::normalizeUrl($url);

        if (trim($token) === '') {
            throw MissingCredentialsException::token();
        }

        $this->transport = new Transport(
            apiUrl: $this->url.'/api/v1',
            token: trim($token),
            client: $httpClient ?? HttpClientFactory::make($timeout),
            requests: $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory(),
            streams: $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory(),
            retry: $retries instanceof RetryPolicy ? $retries : new RetryPolicy(retries: max(0, $retries)),
        );
    }

    public function contacts(): Contacts
    {
        return new Contacts($this->transport);
    }

    public function lists(): Lists
    {
        return new Lists($this->transport);
    }

    public function topics(): Topics
    {
        return new Topics($this->transport);
    }

    public function tags(): Tags
    {
        return new Tags($this->transport);
    }

    public function newsletters(): Newsletters
    {
        return new Newsletters($this->transport);
    }

    public function sequences(): Sequences
    {
        return new Sequences($this->transport);
    }

    public function blocks(): Blocks
    {
        return new Blocks($this->transport);
    }

    public function emailTemplates(): EmailTemplates
    {
        return new EmailTemplates($this->transport);
    }

    public function emailIdentities(): EmailIdentities
    {
        return new EmailIdentities($this->transport);
    }

    public function emailLogs(): EmailLogs
    {
        return new EmailLogs($this->transport);
    }

    public function projects(): Projects
    {
        return new Projects($this->transport);
    }

    public function webhookEndpoints(): WebhookEndpoints
    {
        return new WebhookEndpoints($this->transport);
    }

    /**
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['url' => $this->url];
    }

    /**
     * Accept the instance URL with or without a trailing `/api` or `/api/v1`.
     */
    private static function normalizeUrl(string $url): string
    {
        $url = preg_replace('#(/api(/v1)?)?/*$#', '', trim($url)) ?? '';

        if ($url === '') {
            throw MissingCredentialsException::url();
        }

        return $url;
    }
}
