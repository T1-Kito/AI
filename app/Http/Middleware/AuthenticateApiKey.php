<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-API-Key') ?: $request->query('api_key');

        if (! $key) {
            return response()->json([
                'success' => false,
                'error' => 'Missing API key',
            ], 401);
        }

        $user = User::where('api_key', $key)->first();

        if (! $user || $user->is_admin) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid API key',
            ], 401);
        }

        auth()->setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
