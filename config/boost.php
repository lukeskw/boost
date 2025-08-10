<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Boost Browser Logs Watcher Switch
    |--------------------------------------------------------------------------
    |
    | The following option may be used to enable or disable browser logs watcher
    | functionality which simply provides a single and convenient way to
    | enable or disable browser logs functionality in Boost.
    */
    
    'browser_logs_watcher' => env('BOOST_BROWSER_LOGS_WATCHER', true),
];
