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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Http;

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

        if ($user->registration_status === 'rejected') {
            JWTAuth::invalidate($accessToken);
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda telah ditolak atau dinonaktifkan oleh Admin.',
            ], 403);
        }

        $user->load(['citizen.family.house']);

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

    public function loginFace(Request $request): JsonResponse
    {
        // 1. Validasi Input
        $request->validate([
            'selfie_photo' => 'required|image|max:10240', // Max 10MB
        ]);

        // URL API Python (Ngrok)
        $pythonApiUrl = 'https://caliphal-dallas-wriggly.ngrok-free.dev/find-face';

        try {
            $photo = $request->file('selfie_photo');

            // 2. Kirim Foto ke Python untuk Pencarian 1:N
            $response = Http::withoutVerifying()
                ->timeout(60) // Waktu tunggu agak lama untuk pencarian database
                ->attach(
                    'selfie', // Key ini harus sama dengan parameter di Python (def find_face(selfie...))
                    file_get_contents($photo),
                    $photo->getClientOriginalName()
                )->post($pythonApiUrl);

            $result = $response->json();

            // 3. Cek Response dari Python
            // Harapan response sukses: { "status": "found", "match_filename": "biometrics/nama_file.jpg" }
            if (($result['status'] ?? 'error') !== 'found') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Wajah tidak dikenali atau belum terdaftar.',
                ], 401);
            }

            // 4. Cari User di Database berdasarkan Filename
            // Python mengembalikan path file yang cocok, kita cari di tabel citizens
            $matchedFilename = $result['match_filename'];

            // Tips: Pastikan format path di DB dan di Python konsisten (misal: "biometrics/foto.jpg")
            $citizen = Citizen::where('verified_selfie_photo', $matchedFilename)->first();

            if (!$citizen || !$citizen->user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data user tidak ditemukan (Foto ada tapi user tidak ada).',
                ], 404);
            }

            $user = $citizen->user;

            // 5. Cek Status Akun (PENTING)
            if ($user->registration_status === 'rejected') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akun Anda telah dinonaktifkan.',
                ], 403);
            }

            // 6. Generate Token (Login Sukses)
            $token = JWTAuth::fromUser($user);
            $refreshToken = Str::uuid()->toString();

            RefreshToken::create([
                'user_id'    => $user->id,
                'token'      => $refreshToken,
                'expires_at' => now()->addDays(30),
            ]);

            // Load Data Lengkap
            $user->load(['citizen.family.house']);

            return response()->json([
                'status' => 'success',
                'message' => 'Login berhasil via Wajah',
                'data' => [
                    'access_token'  => $token,
                    'refresh_token' => $refreshToken,
                    'token_type'    => 'bearer',
                    'expires_in'    => JWTAuth::factory()->getTTL() * 60,
                    'user'          => $user
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error("Face Login Error: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Layanan verifikasi wajah sedang sibuk. Silakan gunakan password.',
                'debug' => $e->getMessage() // Hapus line ini saat production
            ], 500);
        }
    }

    /**
     * Register (Updated: Nullable KK Logic)
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validated();

            // --- 0. CEK VERIFIKASI WAJAH (Jika ada selfie) ---
            $selfiePath = null;

            if ($request->hasFile('selfie_photo')) {
                $ktpFile = $request->file('id_card_photo');
                $selfieFile = $request->file('selfie_photo');

                // URL Ngrok Python dari Colab (Ganti setiap kali run Colab)
                $pythonApiUrl = 'https://caliphal-dallas-wriggly.ngrok-free.dev/verify';

                try {
                    $response = Http::withoutVerifying()
                        ->timeout(60)
                        ->attach(
                            'ktp', file_get_contents($ktpFile), $ktpFile->getClientOriginalName()
                        )->attach(
                            'selfie', file_get_contents($selfieFile), $selfieFile->getClientOriginalName()
                        )->post($pythonApiUrl);

                    $result = $response->json();

                    // 1. Cek Error Logika (Wajah Tidak Cocok)
                    // Ini bukan error koneksi, tapi error validasi dari Python
                    if ($response->failed() || ($result['status'] ?? 'error') === 'error') {
                        throw ValidationException::withMessages([
                            'selfie_photo' => [$result['message'] ?? 'Wajah tidak cocok.'],
                        ]);
                    }

                    if ($selfiePath) {
                        try {
                            // $selfiePath contoh: "biometrics/abc12345.jpg"
                            // Kita kirim file fisiknya ke endpoint /add-face

                            // URL Python (Pastikan variabel ini sama dengan yang di atas)
                            $pythonApiUrl = 'https://caliphal-dallas-wriggly.ngrok-free.dev';

                            Http::withoutVerifying()
                                ->timeout(30)
                                ->attach(
                                    'file', // Key sesuai parameter di Python: add_face(file: UploadFile)
                                    file_get_contents(storage_path('app/public/'.$selfiePath)),
                                    basename($selfiePath) // Kirim nama file asli "abc12345.jpg"
                                )
                                ->post($pythonApiUrl . '/add-face');

                        } catch (\Exception $e) {
                            // Silent fail: Jangan batalkan register cuma karena gagal sync
                            // Log error agar kita tahu
                            Log::error("Gagal sync foto ke Colab: " . $e->getMessage());
                        }
                    }

                } catch (ValidationException $e) {
                    throw $e;

                } catch (Exception $e) {

                    Log::error("Face Verification System Error: " . $e->getMessage()); // Log buat admin

                    throw ValidationException::withMessages([
                        'selfie_photo' => ['Gagal menghubungi server verifikasi wajah. Cek koneksi atau coba lagi.'],
                    ]);
                }
            }

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
            $ktpPath = null;
            if ($request->hasFile('id_card_photo')) {
                $ktpPath = $request->file('id_card_photo')->store('id_cards', 'public');
            }

            Citizen::create([
                'user_id'       => $user->id,
                'family_id'     => $family->id,
                'nik'           => $validated['nik'],
                'name'          => $validated['full_name'],
                'phone'         => $validated['phone'],
                'gender'        => $validated['gender'],
                'id_card_photo' => $ktpPath,
                'verified_selfie_photo' => $selfiePath, // <--- Simpan Path Selfie Disini
                'family_role'   => 'Kepala Keluarga',
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
                'message' => 'Register successful' . ($selfiePath ? ' with face verification.' : '.'),
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
     * Refresh JWT Token
     */
    public function refreshToken(RefreshTokenRequest $request): JsonResponse
    {
        $refreshToken = $request->validated()['refresh_token'];
        $oldToken = RefreshToken::where('token', $refreshToken)->first();

        if (!$oldToken || $oldToken->expires_at < now()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Refresh token invalid or expired',
            ], 401);
        }

        // 1. Generate Access Token Baru (JWT String Panjang)
        $accessToken = JWTAuth::fromUser($oldToken->user);

        // 2. Generate Refresh Token Baru (UUID Pendek)
        $newToken = Str::uuid()->toString();

        // Hapus token lama & simpan token baru
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
                // PERBAIKAN DISINI: Gunakan $accessToken, bukan $newToken
                'access_token'  => $accessToken,

                'refresh_token' => $newToken,
                'token_type'    => 'bearer',
                'expires_in'    => JWTAuth::factory()->getTTL() * 60,
            ],
        ]);
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
