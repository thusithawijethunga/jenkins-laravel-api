<?php

namespace JenkinsLaravel;

use Illuminate\Support\ServiceProvider;

class JenkinServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // loading the routes
        // require __DIR__ . "/Http/routes.php";
        $configPath = __DIR__ . '/Config/jenkinapi.php';
        $this->publishes([$configPath => config_path('jenkinapi.php')], 'jenkinapi_config');
        $this->mergeConfigFrom($configPath, 'jenkinapi');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->make('JenkinsLaravel\Jenkins');

        $this->bindFacade();
    }

    private function bindFacade()
    {
        $this->app->bind('jenkins', function ($app) {
            return new Jenkins();
        });
    }
}
