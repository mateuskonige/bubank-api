<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth', except: [
                'register',
                'login',
                'forgot_password',
                'reset_password'
            ]),
        ];
    }

    public function register(StoreUserRequest $request)
    {
        $data = $request->validated();

        User::create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => bcrypt($data->password),
        ]);

        if (!$token = auth()->attempt($request->only(['email', 'password']))) {
            return response()->json([
                'errors' => [
                    'email' => ['These credentials do not match our records.'],
                ],
            ], 422);
        };

        return $this->respond_with_token($token);
    }

    public function login(LoginUserRequest $request)
    {
        if (!$token = auth()->attempt($request->only(['email', 'password']))) {
            return response()->json([
                'errors' => [
                    'email' => ['These credentials do not match our records.'],
                ],
            ], 422);
        }

        return $this->respond_with_token($token);
    }

    public function me()
    {
        return response()->json(auth()->user());
    }

    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh()
    {
        return $this->respond_with_token(auth()->refresh());
    }

    protected function respond_with_token($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60 * 24,
        ]);
    }

    public function forgot_password(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email|max:255',
        ]);

        if (!User::where('email', $validated['email'])->exists()) {
            return response()->json([
                'message' => 'Usuário não encontrado.',
            ]);
        }

        $status = Password::sendResetLink([
            'email' => $validated['email']
        ]);

        return response()->json([
            'status' => __($status),
        ]);
    }

    public function reset_password(UpdateUserPasswordRequest $request)
    {
        $validated = $request->validated();

        $status = Password::reset(
            $validated,
            function ($user) use ($validated) {
                $user->forceFill([
                    'password' => Hash::make($validated['password']),
                    'remember_token' => Str::random(10),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        $status == Password::PASSWORD_RESET;

        return response()->json([
            'status' => __($status),
        ]);
    }
}
