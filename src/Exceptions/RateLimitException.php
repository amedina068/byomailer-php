<?php

declare(strict_types=1);

namespace Byomailer\Exceptions;

/**
 * HTTP 429: the token sent too many requests to a rate-limited endpoint.
 */
final class RateLimitException extends ApiException
{
    /**
     * The number of seconds to wait, from the `Retry-After` header.
     */
    public function retryAfter(): ?int
    {
        return $this->response->retryAfter();
    }
}
