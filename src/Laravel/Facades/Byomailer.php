<?php

declare(strict_types=1);

namespace Byomailer\Laravel\Facades;

use Byomailer\Byomailer as Client;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Byomailer\Resources\Contacts contacts()
 * @method static \Byomailer\Resources\Lists lists()
 * @method static \Byomailer\Resources\Topics topics()
 * @method static \Byomailer\Resources\Tags tags()
 * @method static \Byomailer\Resources\Newsletters newsletters()
 * @method static \Byomailer\Resources\Sequences sequences()
 * @method static \Byomailer\Resources\Blocks blocks()
 * @method static \Byomailer\Resources\EmailTemplates emailTemplates()
 * @method static \Byomailer\Resources\EmailIdentities emailIdentities()
 * @method static \Byomailer\Resources\EmailLogs emailLogs()
 * @method static \Byomailer\Resources\Projects projects()
 * @method static \Byomailer\Resources\WebhookEndpoints webhookEndpoints()
 *
 * @see Client
 */
final class Byomailer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Client::class;
    }
}
