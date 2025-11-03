<?php

namespace erdikoroglu\LaravelServiceScaffold;

use Illuminate\Support\ServiceProvider;
use YourUsername\LaravelServiceScaffold\Commands\MakeServiceScaffold;

class LaravelServiceScaffoldServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeServiceScaffold::class,
            ]);
        }
    }

    public function register(): void
    {
        // Ek bağımlılıklar buraya (gerekirse)
    }
}