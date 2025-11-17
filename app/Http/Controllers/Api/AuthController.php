<?php

namespace App\Http\Controllers\Api;

use App\Models\RefreshToken;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Login (Generate Token)
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!$accessToken = JWTAuth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        $user = auth()->user();

        // Generate refresh token
        $refreshToken = Str::uuid()->toString();

        RefreshToken::create([
            'user_id' => $user->id,
            'token' => $refreshToken,
            'expires_at' => now()->addDays(30),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'access_token'  => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type'    => 'bearer',
                'expires_in'    => JWTAuth::factory()->getTTL() * 60,
                'user' => $user
            ]
        ]);
    }

    /**
     * Refresh JWT Token
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $request->validate([
            'refresh_token' => 'required|uuid',
        ]);

        $oldToken = RefreshToken::where('token', $request->refresh_token)->first();

        if (!$oldToken || $oldToken->expires_at < now()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Refresh token invalid or expired'
            ], 401);
        }

        // Generate new JWT access token
        $accessToken = JWTAuth::fromUser($oldToken->user);

        // Rotate refresh token
        $newToken = Str::uuid()->toString();

        $oldToken->delete();

        RefreshToken::create([
            'user_id' => $oldToken->user_id,
            'token' => $newToken,
            'expires_at' => now()->addDays(30),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Token refreshed successfully',
            'data' => [
                'access_token'  => $accessToken,
                'refresh_token' => $newToken,
                'token_type'    => 'bearer',
                'expires_in'    => JWTAuth::factory()->getTTL() * 60,
            ]
        ]);
    }


    /**
     * Logout (Invalidate Token)
     */
    public function logout(Request $request): JsonResponse
    {
        // Delete all refresh tokens from this user
        RefreshToken::where('user_id', auth()->id())->delete();

        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ]);
    }
}
