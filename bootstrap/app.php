<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use App\Http\Middleware\EnsureEmployeePageAccess;
use App\Http\Middleware\EnsureUserRole;
use App\Http\Middleware\HandleInertiaRequests;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'role'          => EnsureUserRole::class,
            'employee.page' => EnsureEmployeePageAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            $status = $response->getStatusCode();

            if (! in_array($status, [403, 404, 419], true)) {
                return $response;
            }

            $message = match ($status) {
                404 => 'The page you requested could not be found.',
                419 => 'Your session expired. Please sign in again.',
                default => ($exception instanceof HttpExceptionInterface && trim($exception->getMessage()) !== '')
                    ? $exception->getMessage()
                    : 'You do not have permission to access this page.',
            };

            if ($status === 419 || ! $request->user()) {
                return redirect()->route('login')->with('error', $message);
            }

            $previous = url()->previous();
            $target = ($previous && $previous !== $request->fullUrl() && ! str_contains($previous, '/login'))
                ? $previous
                : route('dashboard');

            if ($request->header('X-Inertia') && $target !== $request->fullUrl()) {
                return redirect()->to($target)->with('error', $message);
            }

            $page = Inertia::render('Errors/Show', [
                'status' => $status,
                'message' => $message,
            ])->toResponse($request);

            if (! $request->header('X-Inertia')) {
                $page->setStatusCode($status);
            }

            return $page;
        });
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('documents:archive-expired')->daily();
    })->create();
