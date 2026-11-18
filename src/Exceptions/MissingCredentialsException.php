<?php

declare(strict_types=1);

namespace Byomailer\Exceptions;

/**
 * The client needs a setting that the caller did not give.
 */
final class MissingCredentialsException extends ByomailerException
{
    public static function url(): self
    {
        return new self('Set the URL of your BYOMailer instance. In Laravel, set BYOMAILER_URL.');
    }

    public static function token(): self
    {
        return new self('Set a BYOMailer API token. In Laravel, set BYOMAILER_TOKEN.');
    }

    public static function webhookSecret(): self
    {
        return new self('Set the secret of your webhook endpoint. In Laravel, set BYOMAILER_WEBHOOK_SECRET.');
    }
}
