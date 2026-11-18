<?php

declare(strict_types=1);

namespace Byomailer\Exceptions;

/**
 * HTTP 404: the record does not exist, or it belongs to another client.
 */
final class NotFoundException extends ApiException {}
