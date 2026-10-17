<?php

namespace Devrabiul\CookieConsent\Tests;

use Devrabiul\CookieConsent\CookieConsentServiceProvider;
use Devrabiul\CookieConsent\Facades\CookieConsent;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [CookieConsentServiceProvider::class];
    }

    protected function getPackageAliases($app)
    {
        return ['CookieConsent' => CookieConsent::class];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));
        $app['view']->addLocation(__DIR__ . '/views');
    }
}
