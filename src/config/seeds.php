<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default seeds table
    |--------------------------------------------------------------------------
    |
    | Do not change this! Unless you also change the included migration, since
    | this references the actual table in your database
    |
    */
    'table'     => env('GARDENER_TABLE', 'gardener'),

    /*
    |--------------------------------------------------------------------------
    | Default seeds folder
    |--------------------------------------------------------------------------
    |
    | This option controls the default seeds folder
    |
    */
    'directory' => env('GARDENER_DIRECTORY', 'gardener'),

];
