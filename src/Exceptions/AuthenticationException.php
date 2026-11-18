<?php

declare(strict_types=1);

namespace Byomailer\Exceptions;

/**
 * HTTP 401: the token is missing, not valid or revoked.
 */
final class AuthenticationException extends ApiException {}
