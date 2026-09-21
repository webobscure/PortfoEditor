<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Cookie-based SPA authentication.
 *
 * No API token is ever handed to the browser: the client fetches the CSRF
 * cookie once and then rides the session, which keeps credentials out of
 * JavaScript-readable storage.
 */
final class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->lower()->toString(),
            'password' => Hash::make($request->string('password')->toString()),
        ]);

        Auth::login($user, remember: true);
        $this->regenerateSession($request);

        return response()->json(['data' => $this->profile($user)], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = [
            'email' => $request->string('email')->lower()->toString(),
            'password' => $request->string('password')->toString(),
        ];

        if (! Auth::attempt($credentials, $request->boolean('remember', true))) {
            throw ValidationException::withMessages([
                'email' => 'Неверная почта или пароль.',
            ]);
        }

        $this->regenerateSession($request);

        return response()->json(['data' => $this->profile($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['data' => null]);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->profile($request->user())]);
    }

    /**
     * Rotate the session id after a successful credential check.
     *
     * Sanctum only starts a session for a request it recognises as coming from
     * the SPA, which it decides by matching the Origin/Referer host against
     * `sanctum.stateful`. When that list does not contain the host the browser
     * actually used, there is no session and this call throws — surfacing as an
     * opaque 500 on register and login while GET routes still answer 401. The
     * explicit log line turns that into something readable in the deploy logs.
     */
    private function regenerateSession(Request $request): void
    {
        if (! $request->hasSession()) {
            Log::error('No session on a stateful API request. Sanctum did not recognise the caller as the SPA.', [
                'origin' => $request->headers->get('origin'),
                'referer' => $request->headers->get('referer'),
                'host' => $request->getHost(),
                'stateful_domains' => config('sanctum.stateful'),
                'app_url' => config('app.url'),
            ]);

            throw new RuntimeException(
                'Session unavailable: the request host is not listed in SANCTUM_STATEFUL_DOMAINS.'
            );
        }

        $request->session()->regenerate();
    }

    /** @return array<string, mixed> */
    private function profile(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'initials' => $this->initials($user->name),
        ];
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        $letters = array_map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)), array_slice($parts, 0, 2));

        return implode('', $letters) ?: 'U';
    }
}
