<?php

declare(strict_types=1);

namespace Byomailer\Tests\Laravel;

use Byomailer\Laravel\ByomailerServiceProvider;
use Byomailer\Laravel\Facades\Byomailer;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [ByomailerServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['Byomailer' => Byomailer::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('byomailer.url', 'https://mail.example.com');
        $app['config']->set('byomailer.token', 'test-token');
        $app['config']->set('byomailer.webhook.secret', 'whsec_test0123456789abcdefghijklmnopqrstuvwxyz');
    }
}
