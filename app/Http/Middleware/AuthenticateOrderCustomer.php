<?php

namespace App\Http\Middleware;

use App\Application\Orders\CustomerPortal;
use App\Domain\Orders\CustomerAccessDenied;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateOrderCustomer
{
    public function __construct(private readonly CustomerPortal $portal) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        if (! is_string($token) || ! preg_match('/^[a-f0-9]{64}$/D', $token)) {
            throw new CustomerAccessDenied;
        }
        $hash = hash('sha256', $token);
        if (! $this->portal->isValid((int) $request->route('order'), $hash)) {
            throw new CustomerAccessDenied;
        }
        $request->attributes->set('customer_access_hash', $hash);

        return $next($request);
    }
}
