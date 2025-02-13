<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserPasswordRequest;
use App\Models\Account;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
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

    /**
     * Register a new user.
     * @unauthenticated
     */
    public function register(StoreUserRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
            ]);
            Account::create([
                'user_id' => $user->id,
                'type' => $validated['account_type'],
            ]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw response()->json([
                'message' => $e->getMessage()
            ]);
        }

        if (!$token = auth()->attempt($request->only(['email', 'password']))) {
            return response()->json([
                'errors' => [
                    'email' => ['These credentials do not match our records.'],
                ],
            ], 422);
        };

        return $this->respond_with_token($token);
    }

    /**
     * Login a user.
     * @unauthenticated
     */
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

    /**
     * Get the authenticated User.
     */
    public function me()
    {
        return response()->json(
            array_merge(
                auth()->user()->toArray(),
                [
                    'account' => auth()->user()->account()->first(),
                ]
            )
        );
    }


    /**
     * Log the user out (Invalidate the token).
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     */
    public function refresh()
    {
        return $this->respond_with_token(auth()->refresh());
    }

    /**
     * Send the reset link email
     * @unauthenticated
     */
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

    /**
     * Reset the password
     * @unauthenticated
     */
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

    protected function respond_with_token($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60 * 24,
        ]);
    }
}
