<?php

namespace Laravel\AiAssistant\Http\Middleware;

use Laravel\AiAssistant\AiAssistant;

class Authenticate
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response|null
     */
    public function handle($request, $next)
    {
        return AiAssistant::check($request) ? $next($request) : abort(403);
    }
}
