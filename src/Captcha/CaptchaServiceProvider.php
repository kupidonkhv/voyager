<?php

namespace Mews\Captcha;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\Router;
use Illuminate\Session\Store;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use TCG\Voyager\Support\Image as VoyagerImage;

/**
 * Class CaptchaServiceProvider
 * @package Mews\Captcha
 */
class CaptchaServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish configuration files
        $this->publishes([
            __DIR__ . '/config/captcha.php' => config_path('captcha.php')
        ], 'config');

        // HTTP routing
        if(!config('captcha.disable')){
            if (strpos($this->app->version(), 'Lumen') !== false) {
                /* @var Router $router */
                $router = $this->app;
                $router->get('captcha[/api/{config}]', 'Mews\Captcha\LumenCaptchaController@getCaptchaApi');
                $router->get('captcha[/{config}]', 'Mews\Captcha\LumenCaptchaController@getCaptcha');
            } else {
                /* @var Router $router */
                $router = $this->app['router'];
                if ((double)$this->app->version() >= 5.2) {
                    $router->get('captcha/api/{config?}', '\Mews\Captcha\CaptchaController@getCaptchaApi')->middleware('web');
                    $router->get('captcha/{config?}', '\Mews\Captcha\CaptchaController@getCaptcha')->middleware('web');
                } else {
                    $router->get('captcha/api/{config?}', '\Mews\Captcha\CaptchaController@getCaptchaApi');
                    $router->get('captcha/{config?}', '\Mews\Captcha\CaptchaController@getCaptcha');
                }
            }
        }

        /* @var Factory $validator */
        $validator = $this->app['validator'];

        // Validator extensions
        $validator->extend('captcha', function ($attribute, $value, $parameters) {
            return config('captcha.disable') || ($value && captcha_check($value));
        });

        // Validator extensions
        $validator->extend('captcha_api', function ($attribute, $value, $parameters) {
            return config('captcha.disable') || ($value && captcha_api_check($value, $parameters[0], $parameters[1] ?? 'default'));
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        // Merge configs
        $this->mergeConfigFrom(
            __DIR__ . '/config/captcha.php',
            'captcha'
        );

        if (!$this->app->bound(ImageManager::class)) {
            $this->app->singleton(ImageManager::class, function () {
                return VoyagerImage::manager();
            });
        }

        // Bind captcha
        $this->app->bind('captcha', function ($app) {
            return new Captcha(
                $app[Filesystem::class],
                $app[Repository::class],
                $app[ImageManager::class],
                $app[Store::class],
                $app[Hasher::class],
                new Str()
            );
        });
    }
}
