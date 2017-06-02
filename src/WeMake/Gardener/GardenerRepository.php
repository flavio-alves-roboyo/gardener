<?php

namespace WeMake\Gardener;

use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;

/**
 * Class GardenerRepository
 *
 * @package WeMake\Gardener
 */
class GardenerRepository implements MigrationRepositoryInterface
{
    /**
     * The database connection resolver instance.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * The name of the migration table.
     *
     * @var string
     */
    protected $table;

    /**
     * The name of the database connection to use.
     *
     * @var string
     */
    protected $connection;

    /**
     * The name of the environment to run in.
     *
     * @var string
     */
    public $env;

    /**
     * Create a new database migration repository instance.
     *
     * @param Resolver $resolver
     * @param string   $table
     *
     * @return void
     */
    public function __construct(Resolver $resolver, $table)
    {
        $this->table    = $table;
        $this->resolver = $resolver;
    }

    /**
     * Set the environment to run the seeds against.
     *
     * @param $env
     */
    public function setEnv($env)
    {
        $this->env = $env;
    }

    /**
     * Get the ran migrations.
     *
     * @return array
     */
    public function getRan()
    {
        $env = $this->env ?: App::environment();

        return $this->table()
                    ->where('env', '=', $env)
                    ->orderBy('batch', 'asc')
                    ->orderBy('seed', 'asc')
                    ->pluck('seed')->all();
    }

    /**
     * Get list of migrations.
     *
     * @param int $steps
     *
     * @return array
     */
    public function getMigrations($steps)
    {
        $query = $this->table()->where('batch', '>=', '1');

        return $query->orderBy('seed', 'desc')->take($steps)->get()->all();
    }

    /**
     * Get the last migration batch.
     *
     * @return array
     */
    public function getLast()
    {
        $env = $this->env ?: App::environment();

        $query = $this->table()->where('env', '=', $env)->where('batch', $this->getLastBatchNumber());

        return $query->orderBy('seed', 'desc')->get()->all();
    }

    /**
     * Log that a migration was run.
     *
     * @param string $file
     * @param int    $batch
     *
     * @return void
     */
    public function log($file, $batch)
    {
        $env = $this->env ?: App::environment();

        $record = ['seed' => $file, 'env' => $env, 'batch' => $batch];

        $this->table()->insert($record);
    }

    /**
     * Remove a migration from the log.
     *
     * @param object $seed
     *
     * @return void
     */
    public function delete($seed)
    {
        $env = $this->env ?: App::environment();

        $this->table()->where('env', '=', $env)->where('seed', $seed->seed)->delete();
    }

    /**
     * Get the next migration batch number.
     *
     * @return int
     */
    public function getNextBatchNumber()
    {
        return $this->getLastBatchNumber() + 1;
    }

    /**
     * Get the last migration batch number.
     *
     * @return int
     */
    public function getLastBatchNumber()
    {
        $env = $this->env ?: App::environment();

        return $this->table()->where('env', '=', $env)->max('batch');
    }

    /**
     * Create the migration repository data store.
     *
     * @return void
     */
    public function createRepository()
    {
        $schema = $this->getConnection()->getSchemaBuilder();

        $schema->create($this->table, function (Blueprint $table) {
            // The seeds table is responsible for keeping track of which of the
            // seeds have actually run for the application. We'll create the
            // table to hold the seeds file's path as well as the environment
            // and the batch ID.
            $table->increments('id');
            $table->string('seed');
            $table->string('env');
            $table->integer('batch');
        });
    }

    /**
     * Determine if the migration repository exists.
     *
     * @return bool
     */
    public function repositoryExists()
    {
        $schema = $this->getConnection()->getSchemaBuilder();

        return $schema->hasTable($this->table);
    }

    /**
     * Get a query builder for the migration table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function table()
    {
        return $this->getConnection()->table($this->table)->useWritePdo();
    }

    /**
     * Get the connection resolver instance.
     *
     * @return \Illuminate\Database\ConnectionResolverInterface
     */
    public function getConnectionResolver()
    {
        return $this->resolver;
    }

    /**
     * Resolve the database connection instance.
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    public function getConnection()
    {
        return $this->resolver->connection($this->connection);
    }

    /**
     * Set the information source to gather data.
     *
     * @param string $name
     *
     * @return void
     */
    public function setSource($name)
    {
        $this->connection = $name;
    }
}