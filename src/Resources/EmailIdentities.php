<?php

declare(strict_types=1);

namespace Byomailer\Resources;

use Byomailer\Data\CursorPage;
use Byomailer\Data\EmailIdentity;
use Generator;

/**
 * The sender identities of your client. The API reads identities only.
 */
final readonly class EmailIdentities extends Resource
{
    /**
     * One page of identities, newest first.
     *
     * @param  string|null  $status  Only identities with this SES status, for example `verified`.
     * @return CursorPage<EmailIdentity>
     */
    public function list(?string $status = null, ?int $perPage = null, ?string $cursor = null): CursorPage
    {
        $body = $this->transport->get('email-identities', self::filled([
            'identity_status' => $status,
            'per_page' => $perPage,
            'cursor' => $cursor,
        ]))->json();

        return CursorPage::fromBody($body, EmailIdentity::fromArray(...));
    }

    /**
     * @return Generator<int, EmailIdentity>
     */
    public function all(?string $status = null, ?int $perPage = null): Generator
    {
        return CursorPage::walk(fn (?string $cursor): CursorPage => $this->list($status, $perPage, $cursor));
    }

    public function find(int $emailIdentityId): EmailIdentity
    {
        return EmailIdentity::fromArray($this->transport->get("email-identities/{$emailIdentityId}")->data());
    }
}
