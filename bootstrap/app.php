<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind Vercel's proxy, trust it so links and cookies use https.
        if (env('VERCEL')) {
            $middleware->trustProxies(at: '*');
        }
        $middleware->appendToGroup('web', \App\Http\Middleware\SecurityHeaders::class);
        $middleware->alias(['role' => \App\Http\Middleware\EnsureRole::class]);
        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Demo hosting: show a short, plain error message (no secrets) instead of a blank 500. Turn off with SHOW_ERRORS=0.
        if (env('VERCEL') && env('SHOW_ERRORS')) {
            $exceptions->render(fn (Throwable $e) => $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? null
                : response('Error: '.get_class($e).': '.$e->getMessage(), 500)->header('Content-Type', 'text/plain; charset=utf-8'));
        }
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
