<?php

use App\Domain\Orders\CustomerAccessDenied;
use App\Domain\Shared\BusinessRuleViolation;
use App\Domain\Shared\RecordNotFound;
use App\Http\Middleware\AuthenticateAdmin;
use App\Http\Middleware\AuthenticateOrderCustomer;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['jwt.admin' => AuthenticateAdmin::class, 'order.customer' => AuthenticateOrderCustomer::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*') || $request->expectsJson());
        $exceptions->render(function (RecordNotFound $exception, $request) {
            return response()->json(['message' => 'Registro não encontrado.'], 404);
        });
        $exceptions->render(function (BusinessRuleViolation $exception, $request) {
            return response()->json(['message' => $exception->getMessage()], 409);
        });
        $exceptions->render(function (CustomerAccessDenied $exception, $request) {
            return response()->json(['message' => 'Acesso da OS inválido ou expirado.'], 401)->header('Cache-Control', 'no-store');
        });
        $exceptions->render(function (QueryException $exception, $request) {
            if ($request->is('api/*') && ($exception->errorInfo[0] ?? null) === '23000') {
                return response()->json(['message' => 'Operação em conflito com um registro existente ou vinculado.'], 409);
            }
        });
    })->create();
