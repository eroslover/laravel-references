<?php

namespace Eroslover\References;

use Illuminate\Support\ServiceProvider;

class ReferencesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/references.php' => config_path('references.php')
        ], 'config');
        if (! class_exists('CreateReferencesTable')) {
            $this->publishes([
                __DIR__.'/../database/migrations/create_references_table.php.stub' => database_path(
                    'migrations/'.date('Y_m_d_His', time()).'_create_references_table.php'
                )
            ], 'migrations');
        }
    }
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/references.php', 'references');
    }
}