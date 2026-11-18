<?php

declare(strict_types=1);

namespace Byomailer\Resources;

use Byomailer\Data\CursorPage;
use Byomailer\Data\EmailLog;
use Generator;

/**
 * The log of the Amazon SES calls and notifications of your client.
 */
final readonly class EmailLogs extends Resource
{
    /**
     * One page of logs, newest first.
     *
     * @param  string|null  $recipient  Matches part of the recipient address.
     * @return CursorPage<EmailLog>
     */
    public function list(
        ?string $status = null,
        ?string $operation = null,
        ?string $recipient = null,
        ?int $perPage = null,
        ?string $cursor = null,
    ): CursorPage {
        $body = $this->transport->get('email-logs', self::filled([
            'status' => $status,
            'operation' => $operation,
            'recipient' => $recipient,
            'per_page' => $perPage,
            'cursor' => $cursor,
        ]))->json();

        return CursorPage::fromBody($body, EmailLog::fromArray(...));
    }

    /**
     * @return Generator<int, EmailLog>
     */
    public function all(?string $status = null, ?string $operation = null, ?string $recipient = null, ?int $perPage = null): Generator
    {
        return CursorPage::walk(fn (?string $cursor): CursorPage => $this->list($status, $operation, $recipient, $perPage, $cursor));
    }

    public function find(int $emailLogId): EmailLog
    {
        return EmailLog::fromArray($this->transport->get("email-logs/{$emailLogId}")->data());
    }
}
