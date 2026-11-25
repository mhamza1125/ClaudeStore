<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [\App\Http\Middleware\EnsureAccountIsActive::class, \App\Http\Middleware\SecurityHeaders::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Expired CSRF token (419 "Page Expired"), e.g. signing out from a tab left open past the
        // session lifetime. Instead of the error page: signing out completes and goes home; any
        // other form goes back to its page with the input kept, so nothing typed is lost.
        // JSON/Livewire requests keep the default response (Livewire shows its own prompt).
        // (By the time render callbacks run, the TokenMismatchException is already a 419 HttpException.)
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() !== 419 || $request->expectsJson()) {
                return null;
            }
            if ($request->routeIs('logout')) {
                \Illuminate\Support\Facades\Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('home')->with('status', 'You have been signed out.');
            }

            $back = url()->previous();

            return redirect()->to($back !== $request->fullUrl() ? $back : route('home'))
                ->withInput($request->except(['password', 'password_confirmation', 'current_password', '_token']))
                ->with('warning', 'Your session expired, so that wasn’t sent. Please try again.');
        });
    })->create();
