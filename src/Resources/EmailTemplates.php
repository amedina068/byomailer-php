<?php

declare(strict_types=1);

namespace Byomailer\Resources;

use Byomailer\Data\CursorPage;
use Byomailer\Data\EmailTemplate;
use Generator;

/**
 * The email templates of your client. The API reads templates only.
 */
final readonly class EmailTemplates extends Resource
{
    /**
     * One page of templates, newest first.
     *
     * @param  bool  $archived  True to list the archived templates in place of the active templates.
     * @param  bool|null  $isDefault  True for the default template only. False to leave it out.
     * @return CursorPage<EmailTemplate>
     */
    public function list(bool $archived = false, ?bool $isDefault = null, ?int $perPage = null, ?string $cursor = null): CursorPage
    {
        $body = $this->transport->get('email-templates', self::filled([
            'archived' => $archived ? 1 : null,
            'is_default' => $isDefault === null ? null : (int) $isDefault,
            'per_page' => $perPage,
            'cursor' => $cursor,
        ]))->json();

        return CursorPage::fromBody($body, EmailTemplate::fromArray(...));
    }

    /**
     * @return Generator<int, EmailTemplate>
     */
    public function all(bool $archived = false, ?bool $isDefault = null, ?int $perPage = null): Generator
    {
        return CursorPage::walk(fn (?string $cursor): CursorPage => $this->list($archived, $isDefault, $perPage, $cursor));
    }

    public function find(int $emailTemplateId): EmailTemplate
    {
        return EmailTemplate::fromArray($this->transport->get("email-templates/{$emailTemplateId}")->data());
    }

    /**
     * The default template. The SDK throws a `NotFoundException` when the
     * client has no default template.
     */
    public function default(): EmailTemplate
    {
        return EmailTemplate::fromArray($this->transport->get('email-templates/default')->data());
    }
}
