<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Authenticate user and return a Sanctum token.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validasi Gagal', 422, $validator->errors());
        }

        $loginValue = $request->input('username');
        $field = filter_var($loginValue, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($field, $loginValue)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->errorResponse('Username/Email atau Password salah', 401);
        }

        if (!$user->is_active) {
            return $this->errorResponse('Akun Anda tidak aktif', 403);
        }

        // Generate Sanctum Token
        $tokenName = $request->input('device_name', 'mobile_app');
        $token = $user->createToken($tokenName)->plainTextToken;

        // Update last login
        $user->update([
            'last_login_at' => now(),
        ]);

        return $this->successResponse([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'no_hp' => $user->no_hp,
                'role' => $user->role ? $user->role->name : null,
            ]
        ], 'Login berhasil');
    }

    /**
     * Log the user out (revoke current token).
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logout berhasil');
    }

    /**
     * Retrieve authenticated user details.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->successResponse([
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'no_hp' => $user->no_hp,
            'role' => $user->role ? $user->role->name : null,
        ], 'Berhasil mengambil data profil');
    }
}
