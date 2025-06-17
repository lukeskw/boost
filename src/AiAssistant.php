<?php

namespace Laravel\AiAssistant;

use Closure;

class AiAssistant
{
    /**
     * The callback that should be used to authenticate AiAssistant users.
     *
     * @var \Closure
     */
    public static $authUsing;

    /**
     * Determine if the given request can access the AiAssistant dashboard.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public static function check($request)
    {
        return (static::$authUsing ?: function () {
            return app()->environment('local');
        })($request);
    }

    /**
     * Set the callback that should be used to authenticate AiAssistant users.
     *
     * @return static
     */
    public static function auth(Closure $callback)
    {
        static::$authUsing = $callback;

        return new static;
    }
}
