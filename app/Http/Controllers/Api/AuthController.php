<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Citizen;
use App\Models\Family;
use App\Models\House;
use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Login (Generate Token)
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if (!$accessToken = JWTAuth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        /** @var User $user */
        $user = auth()->user();

        // Load relasi citizen agar frontend tahu siapa yang login
        // Load juga family dan house untuk kebutuhan dashboard
        $user->load(['citizen.family.house']);

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
                'user' => $user // User object sekarang include citizen & role
            ]
        ]);
    }

    /**
     * Refresh JWT Token
     */
    public function refreshToken(RefreshTokenRequest $request): JsonResponse
    {
        // ... (Bagian ini TIDAK PERLU DIUBAH, kode lama Anda sudah oke)
        $refreshToken = $request->validated()['refresh_token'];
        $oldToken = RefreshToken::where('token', $refreshToken)->first();

        if (!$oldToken || $oldToken->expires_at < now()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Refresh token invalid or expired',
            ], 401);
        }

        $accessToken = JWTAuth::fromUser($oldToken->user);
        $newToken = Str::uuid()->toString();
        $oldToken->delete();

        RefreshToken::create([
            'user_id'    => $oldToken->user_id,
            'token'      => $newToken,
            'expires_at' => now()->addDays(30),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Token refreshed successfully',
            'data'    => [
                'access_token'  => $newToken, // Typo fix: biasanya return access token baru
                'refresh_token' => $newToken,
                'token_type'    => 'bearer',
                'expires_in'    => JWTAuth::factory()->getTTL() * 60,
            ],
        ]);
    }

    /**
     * Register (Updated: Nullable KK Logic)
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validated();

            // 1. Create User Account (SAMA)
            $user = User::create([
                'name'     => $validated['full_name'],
                'email'    => $validated['email'],
                'phone'    => $validated['phone'],
                'password' => bcrypt($validated['password']),
                'role'     => 'resident',
                'registration_status' => 'pending',
            ]);

            // 2. Handle Housing (SAMA)
            $houseId = $validated['house_id'] ?? null;

            if (!$houseId && !empty($validated['custom_house_address'])) {
                $house = House::create([
                    'house_name' => 'Rumah ' . $validated['full_name'],
                    'owner_name' => $validated['full_name'],
                    'address'    => $validated['custom_house_address'],
                    'status'     => 'occupied',
                    'house_type' => 'Unofficial',
                ]);
                $houseId = $house->id;
            }

            // 3. Handle Family (KK) - UPDATE DISINI
            // HAPUS logic: $tempKK = 'TMP-' . time() ...

            $family = Family::create([
                'house_id'         => $houseId,
                // Ambil dari request jika ada, jika tidak ada set NULL
                'kk_number'        => $validated['kk_number'] ?? null,
                'ownership_status' => $validated['ownership_status'] ?? 'owner',
                'status'           => 'active'
            ]);

            // 4. Handle Citizen (Warga) (SAMA)
            $photoPath = null;
            if ($request->hasFile('id_card_photo')) {
                $photoPath = $request->file('id_card_photo')->store('id_cards', 'public');
            }

            Citizen::create([
                'user_id'       => $user->id,
                'family_id'     => $family->id,
                'nik'           => $validated['nik'],
                'name'          => $validated['full_name'],
                'phone'         => $validated['phone'],
                'gender'        => $validated['gender'],
                'id_card_photo' => $photoPath,
                'family_role'   => 'Kepala Keluarga', // Default
                'birth_place'   => null,
                'birth_date'    => null,
                'religion'      => null,
                'blood_type'    => null,
                'status'        => 'permanent'
            ]);

            // 5. Generate Token (SAMA)
            $accessToken  = JWTAuth::fromUser($user);
            $refreshToken = Str::uuid()->toString();

            RefreshToken::create([
                'user_id'    => $user->id,
                'token'      => $refreshToken,
                'expires_at' => now()->addDays(30),
            ]);

            $user->refresh();
            $user->load(['citizen.family.house']);

            return response()->json([
                'status'  => 'success',
                'message' => 'Register successful',
                'data'    => [
                    'access_token'  => $accessToken,
                    'refresh_token' => $refreshToken,
                    'token_type'    => 'bearer',
                    'expires_in'    => JWTAuth::factory()->getTTL() * 60,
                    'user'          => $user,
                ],
            ], 201);
        });
    }

    /**
     * Logout
     */
    public function logout(Request $request): JsonResponse
    {
        RefreshToken::where('user_id', auth()->id())->delete();

        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (\Exception $e) {
            // Token expired or invalid, just ignore
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ]);
    }
}
