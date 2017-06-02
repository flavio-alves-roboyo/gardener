<?php

namespace WeMake\Gardener;

/**
 * Class Seeder
 */
class Seeder extends Eloquent
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'seeds';

    /**
     * Fillable properties.
     *
     * @var [type]
     */
    protected $fillable = [
        'seed',
        'env',
        'batch',
    ];
}
