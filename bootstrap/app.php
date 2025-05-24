<?php

use App\Http\Middleware\JwtMiddleware;
use App\Http\Middleware\OnlyUserCanAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use App\Jobs\UpdateExamActiveStatus;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // middlewares...
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // exception handling...
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->job(new UpdateExamActiveStatus)->everyMinute();
    })
    ->create();
