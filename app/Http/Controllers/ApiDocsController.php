<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ApiDocsController extends Controller
{
    public function show(): View
    {
        abort_if(auth()->user()->is_admin, 403);

        $user = auth()->user();

        if (! $user->api_key) {
            $user = $this->rotateKeyFor($user);
        }

        return view('api.docs', [
            'apiKey' => $user->api_key,
            'baseUrl' => url('/'),
        ]);
    }

    public function rotate(): RedirectResponse
    {
        abort_if(auth()->user()->is_admin, 403);

        $this->rotateKeyFor(auth()->user());

        return back()->with('success', 'Da doi API key moi.');
    }

    private function rotateKeyFor(User $user): User
    {
        do {
            $key = hash('sha256', Str::random(48) . now()->timestamp);
        } while (User::where('api_key', $key)->exists());

        $user->forceFill(['api_key' => $key])->save();

        return $user->fresh();
    }
}
