<?php

declare(strict_types=1);

namespace Byomailer\Webhooks;

/**
 * The reason why a webhook delivery is not valid.
 */
enum VerificationFailure: string
{
    /**
     * The signature header is missing, or it does not match the body.
     */
    case Signature = 'signature';

    /**
     * The delivery was signed outside the tolerance.
     */
    case Timestamp = 'timestamp';

    /**
     * The signature is valid, but the body is not a JSON object.
     */
    case Payload = 'payload';
}
