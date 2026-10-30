<?php

return [
    'sqlite_database' => database_path('database.sqlite'),

    /*
    |--------------------------------------------------------------------------
    | Test Installation State
    |--------------------------------------------------------------------------
    |
    | Production always uses the marker in storage/app/installed. Tests can
    | override this value to exercise either side of the first-run gate.
    |
    */
    'testing_installed' => true,
];
