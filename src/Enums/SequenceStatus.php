<?php

declare(strict_types=1);

namespace Byomailer\Enums;

/**
 * The status of a sequence. Only a live sequence sends email.
 */
enum SequenceStatus: string
{
    case Draft = 'draft';
    case Live = 'live';
    case Paused = 'paused';
}
