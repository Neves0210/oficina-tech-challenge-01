<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Infrastructure\Security\AdminToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TokenController extends Controller
{
    public function __invoke(Request $request, AdminToken $tokens): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);
        $user = User::where('email', $credentials['email'])->first();
        // Executa a verificação mesmo para contas inexistentes, evitando um atalho de tempo.
        $hash = $user?->password ?? '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        if (! Hash::check($credentials['password'], $hash) || ! $user?->is_admin) {
            return response()->json(['message' => 'Credenciais inválidas.'], 401);
        }

        return response()->json([
            'access_token' => $tokens->issue($user->id),
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl'),
        ]);
    }
}
