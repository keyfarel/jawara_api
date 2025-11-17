<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use App\Http\Middleware\ApiAuthenticate;
use App\Http\Middleware\ForceJson;
use App\Http\Middleware\ForceJsonAuth;
use App\Exceptions\AuthExceptionHandler;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders()
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        // api: __DIR__.'/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        // channels: __DIR__.'/../routes/channels.php',
        health: '/up',

        then: function () {
            Route::middleware('web')->group(__DIR__ . '/../routes/app.php');
            Route::middleware([ForceJson::class])->group(__DIR__ . '/../routes/app-api.php');
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth' => ForceJsonAuth::class,
            'jwt.auth' => ApiAuthenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        $exceptions->renderable(function (UnauthorizedHttpException $e, $request) {
            return AuthExceptionHandler::handle($e, $request);
        });

    })
->create();
