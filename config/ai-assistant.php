<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AiAssistant Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where AiAssistant will be accessible from. If this
    | setting is null, AiAssistant will reside under the same domain as the
    | application. Otherwise, this value will serve as the subdomain.
    |
    */

    'domain' => env('AI_ASSISTANT_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | AiAssistant Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where AiAssistant will be accessible from. Feel free
    | to change this path to anything you like. Note that the URI will not
    | affect the paths of its internal API that aren't exposed to users.
    |
    */

    'path' => env('AI_ASSISTANT_PATH', 'ai-assistant'),

    /*
    |--------------------------------------------------------------------------
    | AiAssistant Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will get attached onto each AiAssistant route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */

    'middleware' => ['web'],

];
