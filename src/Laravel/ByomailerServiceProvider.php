<?php

declare(strict_types=1);

namespace Byomailer\Laravel;

use Byomailer\Byomailer;
use Byomailer\Webhooks\Webhook;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

final class ByomailerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/byomailer.php', 'byomailer');

        $this->app->singleton(Byomailer::class, static function (Application $app): Byomailer {
            $config = $app->make(Repository::class);

            return new Byomailer(
                url: self::string($config->get('byomailer.url')),
                token: self::string($config->get('byomailer.token')),
                timeout: is_numeric($timeout = $config->get('byomailer.timeout')) ? (float) $timeout : 30.0,
                retries: is_numeric($retries = $config->get('byomailer.retries')) ? (int) $retries : 2,
            );
        });

        $this->app->singleton(Webhook::class, static function (Application $app): Webhook {
            $config = $app->make(Repository::class);

            return new Webhook(
                secret: self::string($config->get('byomailer.webhook.secret')),
                tolerance: is_numeric($tolerance = $config->get('byomailer.webhook.tolerance')) ? (int) $tolerance : 300,
            );
        });

        $this->app->alias(Byomailer::class, 'byomailer');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/byomailer.php' => $this->app->configPath('byomailer.php'),
            ], 'byomailer-config');
        }
    }

    private static function string(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }
}
