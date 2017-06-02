<?php

namespace WeMake\Gardener;

use Illuminate\Console\DetectsApplicationNamespace;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Class SeedMigrator
 *
 * @package WeMake\Gardener
 */
class SeedMigrator extends Migrator
{
    use DetectsApplicationNamespace;

    /**
     * Create a new migrator instance.
     *
     * @param \WeMake\Gardener\GardenerRepository              $repository
     * @param \Illuminate\Database\ConnectionResolverInterface $resolver
     * @param \Illuminate\Filesystem\Filesystem                $files
     *
     */
    public function __construct(GardenerRepository $repository, Resolver $resolver, Filesystem $files)
    {
        parent::__construct($repository, $resolver, $files);
    }

    /**
     * Set env.
     *
     * @param string $env
     */
    public function setEnv($env)
    {
        $this->repository->setEnv($env);
    }

    /**
     * Get all of the migration files in a given path.
     *
     * @param string|array $paths
     *
     * @return array
     */
    public function getMigrationFiles($paths)
    {
        return Collection::make($paths)->flatMap(function ($path) {
            $pattern = $this->repository->env
                ? "$path/{$this->repository->env}/*.php"
                : "$path/*.php";

            return $this->files->glob($pattern);
        })->filter()->sortBy(function ($file) {
            return $this->getMigrationName($file);
        })->values()->keyBy(function ($file) {
            return $this->getMigrationName($file);
        })->all();
    }

    /**
     * Run the outstanding migrations at a given path.
     *
     * @param string $path
     * @param bool   $pretend
     *
     * @return void
     */
    public function runSingleFile($path, $pretend = false)
    {
        $this->notes = [];

        $file  = str_replace('.php', '', basename($path));
        $files = [$file];

        // Once we grab all of the migration files for the path, we will compare them
        // against the migrations that have already been run for this package then
        // run each of the outstanding migrations against a database connection.
        $ran = $this->repository->getRan();

        $migrations   = array_diff($files, $ran);
        $filename_ext = pathinfo($path, PATHINFO_EXTENSION);

        if ( ! $filename_ext) {
            $path .= '.php';
        }

        $this->files->requireOnce($path);

        $this->runMigrationList($migrations, $pretend);
    }

    /**
     * Run "up" a migration instance.
     *
     * @param string $file
     * @param int    $batch
     * @param bool   $pretend
     *
     * @return void
     */
    protected function runUp($file, $batch, $pretend)
    {
        // First we will resolve a "real" instance of the migration class from this
        // migration file name. Once we have the instances we can run the actual
        // command such as "up" or "down", or we can just simulate the action.
        $migration = $this->resolve(
            $name = $this->getMigrationName($file)
        );

        if ($pretend) {
            return $this->pretendToRun($migration, 'up');
        }

        $this->note("<comment>Seeding:</comment> {$name}");

        $this->runMigration($migration, 'up');

        // Once we have run a migrations class, we will log that it was run in this
        // repository so that we don't try to run it next time we do a migration
        // in the application. A migration repository keeps the migrate order.
        $this->repository->log($name, $batch);

        $this->note("<info>Seeded:</info>  {$name}");
    }

    /**
     * Run "down" a migration instance.
     *
     * @param string $file
     * @param object $migration
     * @param bool   $pretend
     *
     * @return void
     */
    protected function runDown($file, $migration, $pretend)
    {
        // First we will get the file name of the migration so we can resolve out an
        // instance of the migration. Once we get an instance we can either run a
        // pretend execution of the migration or we can run the real migration.
        $instance = $this->resolve($file);
        $name     = $this->getMigrationName($file);

        if ($pretend) {
            return $this->pretendToRun($instance, 'down');
        }

        $this->note("<comment>Rolling back:</comment> {$name}");

        if (method_exists($instance, 'down')) {
            $instance->down();
        }

        // Once we have successfully run the migration "down" we will remove it from
        // the migration repository so it will be considered to have not been run
        // by the application then will be able to fire by any later operation.
        $this->repository->delete($migration);

        $this->note("<info>Rolled back:</info> $name");
    }

    /**
     * Rollback the given migrations.
     *
     * @param  array        $seeds
     * @param  array|string $paths
     * @param  array        $options
     *
     * @return array
     */
    protected function rollbackMigrations(array $seeds, $paths, array $options)
    {
        $rolledBack = [];

        $this->requireFiles($files = $this->getMigrationFiles($paths));

        // Next we will run through all of the migrations and call the "down" method
        // which will reverse each migration in order. This getLast method on the
        // repository already returns these migration's names in reverse order.
        foreach ($seeds as $seed) {
            $seed = (object)$seed;

            $rolledBack[] = $files[$seed->seed];

            $this->runDown(
                $files[$seed->seed],
                $seed, Arr::get($options, 'pretend', false)
            );
        }

        return $rolledBack;
    }

    /**
     * Reset the given seeds.
     *
     * @param  array $seeds
     * @param  array $paths
     * @param  bool  $pretend
     *
     * @return array
     */
    protected function resetMigrations(array $seeds, array $paths, $pretend = false)
    {
        // Since the getRan method that retrieves the migration name just gives us the
        // migration name, we will format the names into objects with the name as a
        // property on the objects so that we can pass it to the rollback method.
        $seeds = collect($seeds)->map(function ($m) {
            return (object)['seed' => $m];
        })->all();

        return $this->rollbackMigrations(
            $seeds, $paths, compact('pretend')
        );
    }

    /**
     * Resolve a migration instance from a file.
     *
     * @param  string  $file
     * @return object
     */
    public function resolve($file)
    {
        $class = Str::studly(implode('_', array_slice(explode('_', $file), 4)));

        $class = str_replace('.php', '', $class);

        return new $class;
    }
}