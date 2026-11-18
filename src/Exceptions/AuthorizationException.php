<?php

declare(strict_types=1);

namespace Byomailer\Exceptions;

/**
 * HTTP 403: the token cannot do this request.
 *
 * A read-only token gets this error on each request that changes data. A
 * token of a user who is not an admin gets it on each webhook endpoint
 * request.
 */
final class AuthorizationException extends ApiException {}
