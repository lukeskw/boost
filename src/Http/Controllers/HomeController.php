<?php

namespace Laravel\AiAssistant\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Laravel\AiAssistant\Http\Middleware\Authenticate;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(Authenticate::class);
    }

    /**
     * Single page application catch-all route.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return view('ai-assistant::layout', [
            'isDownForMaintenance' => App::isDownForMaintenance(),
            'scriptVariables' => [
                'path' => config('ai-assistant.path'),
                'timezone' => config('app.timezone'),
            ],
        ]);
    }
}
