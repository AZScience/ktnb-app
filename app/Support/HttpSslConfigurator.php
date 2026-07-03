<?php

namespace App\Support;

use Composer\CaBundle\CaBundle;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;

class HttpSslConfigurator
{
    /** @var callable|null */
    private static $googleHttpHandler = null;

    public static function apply(): void
    {
        $caBundle = self::resolvePath();
        if ($caBundle === null) {
            return;
        }

        ini_set('curl.cainfo', $caBundle);
        ini_set('openssl.cafile', $caBundle);

        Http::globalOptions(['verify' => $caBundle]);
    }

    public static function guzzleClient(array $options = []): Client
    {
        $caBundle = self::resolvePath();
        if ($caBundle !== null) {
            $options['verify'] = $caBundle;
        }

        return new Client($options);
    }

    public static function googleHttpHandler(): ?callable
    {
        if (self::$googleHttpHandler !== null) {
            return self::$googleHttpHandler;
        }

        $caBundle = self::resolvePath();
        if ($caBundle === null) {
            return null;
        }

        self::$googleHttpHandler = HttpHandlerFactory::build(
            self::guzzleClient(),
        );

        return self::$googleHttpHandler;
    }

    public static function resolvePath(): ?string
    {
        $candidates = [];

        if (class_exists(CaBundle::class)) {
            $candidates[] = CaBundle::getBundledCaBundlePath();
            $candidates[] = CaBundle::getSystemCaRootBundlePath();
        }

        $candidates[] = base_path('vendor/composer/ca-bundle/res/cacert.pem');
        $candidates[] = base_path('storage/app/cacert.pem');
        $candidates[] = base_path('cacert.pem');

        foreach ($candidates as $path) {
            if (is_string($path) && $path !== '' && is_readable($path)) {
                return $path;
            }
        }

        return null;
    }
}
