<?php

namespace Lcmaquino\GoogleOAuth2;

use Illuminate\Support\ServiceProvider;

class GoogleOAuth2Provider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(GoogleOAuth2Manager::class, function($app){
            return new GoogleOAuth2Manager(config('googleoauth2'), $app->request);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [GoogleOAuth2Manager::class];
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $source = realpath(__DIR__ . '/config/googleoauth2.php');
        
        $this->publishes([$source => config_path('googleoauth2.php')]);
        $this->mergeConfigFrom($source, 'googleoauth2');
    }
}