<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CheckSingleSession;
use App\Http\Middleware\UpdateLastSeen;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        
        $middleware->authenticateSessions();

        $middleware->alias([
            'single.session' => CheckSingleSession::class,
            'update.last.seen' => UpdateLastSeen::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();