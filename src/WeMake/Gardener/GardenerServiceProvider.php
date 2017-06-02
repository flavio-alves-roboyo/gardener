<?php

namespace WeMake\Gardener;

use Illuminate\Support\ServiceProvider;

/**
 * Class GardenerServiceProvider
 *
 * @package WeMake\Gardener
 */
class GardenerServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Boot.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/gardener.php' => config_path('gardener.php'),
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/gardener.php', 'gardener'
        );

        $this->app->singleton('seed.repository', function ($app) {
            return new GardenerRepository($app['db'], config('gardener.table'));
        });

        $this->app->singleton('seed.migrator', function ($app) {
            return new SeedMigrator($app['seed.repository'], $app['db'], $app['files']);
        });

        $this->app->singleton('seed.creator', function ($app) {
            return new SeedCreator($app['files']);
        });

        $this->app->bind('command.seed', function ($app) {
            return new SeedOverrideCommand($app['seed.migrator']);
        });

        $this->app->bind('seed.run', function ($app) {
            return new SeedCommand($app['seed.migrator']);
        });

        $this->app->bind('seed.install', function ($app) {
            return new SeedInstallCommand($app['seed.repository']);
        });

        $this->app->bind('seed.make', function ($app) {
            // Once we have the migration creator registered, we will create the command
            // and inject the creator. The creator is responsible for the actual file
            // creation of the migrations, and may be extended by these developers.
            $creator = $app['seed.creator'];

            $composer = $app['composer'];

            return new SeedMakeCommand($creator, $composer);
        });

        $this->app->bind('seed.reset', function ($app) {
            return new SeedResetCommand($app['seed.migrator']);
        });

        $this->app->bind('seed.rollback', function ($app) {
            return new SeedRollbackCommand($app['seed.migrator']);
        });

        $this->app->bind('seed.refresh', function () {
            return new SeedRefreshCommand();
        });

        $this->commands([
            'seed.run',
            'seed.install',
            'seed.make',
            'seed.reset',
            'seed.rollback',
            'seed.refresh',
        ]);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'seed.repository',
            'seed.migrator',
            'command.seed',
            'seed.run',
            'seed.install',
            'seed.make',
            'seed.reset',
            'seed.rollback',
            'seed.refresh',
        ];
    }
}
