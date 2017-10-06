<?php

namespace WeMake\Gardener;

use Illuminate\Console\DetectsApplicationNamespace;
use Illuminate\Support\Composer;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class SeedMakeCommand
 *
 * @package WeMake\Gardener
 */
class SeedMakeCommand extends SeedBaseCommand
{
    use DetectsApplicationNamespace;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'seed:make';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new seed file';

    /**
     * The migration creator instance.
     *
     * @var \Illuminate\Database\Migrations\MigrationCreator
     */
    protected $creator;

    /**
     * The Composer instance.
     *
     * @var \Illuminate\Support\Composer
     */
    protected $composer;

    /**
     * Create a new migration install command instance.
     *
     * @param \WeMake\Gardener\SeedCreator $creator
     * @param \Illuminate\Support\Composer $composer
     */
    public function __construct(SeedCreator $creator, Composer $composer)
    {
        parent::__construct();

        $this->creator  = $creator;
        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // It's possible for the developer to specify the tables to modify in this
        // schema operation. The developer may also specify if this table needs
        // to be freshly created so we can create the appropriate migrations.
        $name = trim($this->input->getArgument('name'));

        // Now we are ready to write the migration out to disk. Once we've written
        // the migration out, we will dump-autoload for the entire framework to
        // make sure that the migrations are registered by the class loaders.
        $this->writeMigration($name);

        $this->composer->dumpAutoloads();
    }

    /**
     * Write the migration file to disk.
     *
     * @param string $name
     *
     * @return void
     */
    protected function writeMigration($name)
    {
        $file = pathinfo($this->creator->create(
            $name, $this->getMigrationPath()
        ), PATHINFO_FILENAME);

        $this->line("<info>Created Migration:</info> {$file}");
    }

    /**
     * Get migration path (either specified by '--path' option or default location).
     *
     * @return string
     */
    protected function getMigrationPath()
    {
        $gardenerDirectory = config('gardener.directory');

        $targetPath = "{$this->laravel->databasePath()}/$gardenerDirectory";

        if ( ! is_null($path = $this->input->getOption('path'))) {
            $targetPath .= "{$this->laravel->basePath()}/$path";
        }

        if ( ! is_null($env = $this->input->getOption('env'))) {
            $targetPath .= "/$env";
        }

        return $targetPath;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the seed you wish to create.'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['env', null, InputOption::VALUE_OPTIONAL, 'The environment to seed to.', null],
            ['path', null, InputOption::VALUE_OPTIONAL, 'The relative path to the base path to generate the seed to.', null],
        ];
    }
}