<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Database\Eloquent\Attributes\UseResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        //$request->session()->regenerate();
        $user = $request->user();
        $token = $user->createToken($user->name)->plainTextToken;

        return [
            'user' => new UseResource($user),
            'token' => $token,
        ];
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): Response
    {
        /*Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();*/
        $user = $request->user();
        $user->currentAccessToken()->delete();

        return response()->noContent();
    }
}
