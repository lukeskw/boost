<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Boost Project Purpose
    |--------------------------------------------------------------------------
    |
    | This is the purpose of the Boost project helping AI understand context
    | the project and its purpose and be guidelines more specific
    | project. This should be set to a very specific.
    |
    */

    'project_purpose' => null,

    /*
    |--------------------------------------------------------------------------
    | Boost Browser Logs Watcher Switch
    |--------------------------------------------------------------------------
    |
    | The following option may be used to enable or disable browser logs watcher
    | functionality which simply provides a single and convenient way to
    | enable or disable browser logs functionality in Boost.
    */
    'boost_browser_logs_watcher' => env('BOOST_BROWSER_LOGS_WATCHER', true),
];
