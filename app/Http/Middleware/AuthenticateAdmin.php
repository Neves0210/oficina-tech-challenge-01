<?php

namespace App\Http\Middleware;

use App\Infrastructure\Security\AdminToken;
use App\Models\User;
use Closure;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use UnexpectedValueException;

class AuthenticateAdmin
{
    public function __construct(private readonly AdminToken $tokens) {}

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $token = $request->bearerToken();
            if (! $token) {
                throw new UnexpectedValueException;
            }
            $user = User::find($this->tokens->userId($token));
            if (! $user) {
                throw new UnexpectedValueException;
            }
        } catch (UnexpectedValueException|DomainException $exception) {
            return response()->json(['message' => 'Não autenticado.'], 401);
        }

        if (! $user->is_admin) {
            return response()->json(['message' => 'Acesso administrativo necessário.'], 403);
        }

        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
