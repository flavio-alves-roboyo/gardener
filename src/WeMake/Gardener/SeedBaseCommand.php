<?php

namespace WeMake\Gardener;

use Illuminate\Console\Command;

/**
 * Class SeedBaseCommand
 *
 * @package WeMake\Gardener
 */
class SeedBaseCommand extends Command
{
    /**
     * Get all of the seeds paths.
     *
     * @return array
     */
    protected function getSeedPaths()
    {
        // Here, we will check to see if a path option has been defined. If it has we will
        // use the path relative to the root of the installation folder so our database
        // migrations may be run for any customized path from within the application.
        if ($this->input->hasOption('path') && $this->option('path')) {
            return collect($this->option('path'))->map(function ($path) {
                return $this->laravel->basePath() . '/' . $path;
            })->all();
        }

        return array_merge(
            [$this->getSeedsPath()], $this->migrator->paths()
        );
    }

    /**
     * Get the path to the seeds directory.
     *
     * @return string
     */
    protected function getSeedsPath()
    {
        return $this->laravel->databasePath(config('seeds.directory'));
    }
}
