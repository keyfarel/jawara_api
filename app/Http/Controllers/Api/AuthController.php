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

            $matchedFilename = $result['match_filename'];
            // Python code Anda mengembalikan clean filename: "foto.jpg"

            // Karena di database kita simpan "biometrics/foto.jpg", kita harus sesuaikan pencariannya
            // OPSI 1: Cari pakai LIKE (Paling aman)
            $citizen = Citizen::where('verified_selfie_photo', 'LIKE', '%' . $matchedFilename)->first();

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

            $selfiePath = null;
            $ktpPath = null;

            // Base URL Python
            $pythonApiUrl = 'https://caliphal-dallas-wriggly.ngrok-free.dev';

            // --- 1. PROSES FILE & VERIFIKASI WAJAH ---
            if ($request->hasFile('selfie_photo') && $request->hasFile('id_card_photo')) {
                $ktpFile = $request->file('id_card_photo');
                $selfieFile = $request->file('selfie_photo');

                // ============================================================
                // A.0 CEK DUPLIKASI WAJAH (Mencegah Double Account) [BARU]
                // ============================================================
                try {
                    // Kita gunakan endpoint /find-face yang biasa dipakai login
                    $duplicateCheck = Http::withoutVerifying()
                        ->timeout(30)
                        ->attach('selfie', file_get_contents($selfieFile), $selfieFile->getClientOriginalName())
                        ->post($pythonApiUrl . '/find-face');

                    $duplicateResult = $duplicateCheck->json();

                    // LOGIKA: Jika STATUS = FOUND, artinya wajah SUDAH ADA -> Error!
                    if (($duplicateResult['status'] ?? 'error') === 'found') {

                        // (Opsional) Cek siapa pemilik wajah ini di database Laravel
                        $matchedFilename = $duplicateResult['match_filename'];
                        $existingUser = Citizen::where('verified_selfie_photo', 'LIKE', '%' . $matchedFilename)->first();
                        $msg = $existingUser
                            ? "Wajah ini sudah terdaftar di sistem. Silakan login."
                            : "Wajah ini sudah terdaftar di sistem.";

                        throw ValidationException::withMessages([
                            'selfie_photo' => [$msg],
                        ]);
                    }

                } catch (ValidationException $e) {
                    throw $e; // Lempar error validasi ke user
                } catch (\Exception $e) {
                    // Jika server Python mati/timeout saat cek duplikat,
                    // Anda bisa memilih: Lanjut (Warning) atau Blokir (Strict).
                    // Disini saya pilih Log Warning agar registrasi tidak macet total kalau AI down.
                    \Log::warning("Gagal cek duplikasi wajah: " . $e->getMessage());
                }

                // ============================================================
                // A.1 Verifikasi Biometrik (KTP vs Selfie) [EXISTING]
                // ============================================================
                try {
                    $response = Http::withoutVerifying()
                        ->timeout(60)
                        ->attach('ktp', file_get_contents($ktpFile), $ktpFile->getClientOriginalName())
                        ->attach('selfie', file_get_contents($selfieFile), $selfieFile->getClientOriginalName())
                        ->post($pythonApiUrl . '/verify');

                    $result = $response->json();

                    if ($response->failed() || ($result['status'] ?? 'error') === 'error') {
                        throw ValidationException::withMessages([
                            'selfie_photo' => [$result['message'] ?? 'Wajah tidak cocok dengan KTP.'],
                        ]);
                    }

                    // B. Simpan File ke Storage Laravel
                    $selfiePath = $selfieFile->store('biometrics', 'public');
                    $ktpPath = $ktpFile->store('id_cards', 'public');

                    // C. Sinkronisasi ke Python (Add Face)
                    try {
                        $storagePath = storage_path('app/public/' . $selfiePath);
                        $filenameOnly = basename($selfiePath);

                        Http::withoutVerifying()
                            ->timeout(30)
                            ->attach('file', file_get_contents($storagePath), $filenameOnly)
                            ->post($pythonApiUrl . '/add-face');

                    } catch (\Exception $e) {
                        \Log::error("Gagal Sync Wajah ke Python: " . $e->getMessage());
                    }

                } catch (ValidationException $e) {
                    throw $e;
                } catch (\Exception $e) {
                    \Log::error("Face Verification System Error: " . $e->getMessage());
                    throw ValidationException::withMessages([
                        'selfie_photo' => ['Sistem verifikasi wajah sedang gangguan.'],
                    ]);
                }
            } else {
                // Fallback jika tidak wajib upload foto (tergantung aturan bisnis Anda)
                if ($request->hasFile('id_card_photo')) {
                    $ktpPath = $request->file('id_card_photo')->store('id_cards', 'public');
                }
            }

            // --- 2. Create User Account ---
            $user = User::create([
                'name'     => $validated['full_name'],
                'email'    => $validated['email'],
                'phone'    => $validated['phone'],
                'password' => bcrypt($validated['password']),
                'role'     => 'resident',
                'registration_status' => 'pending',
            ]);

            // --- 3. Handle Housing ---
            $houseId = $request->house_id;

            // JIKA MEMBUAT RUMAH BARU
            if (!$houseId) {
                // Ambil data dari request baru
                $block = $request->house_block;
                $number = $request->house_number;
                $street = $request->house_street;

                // Generate Nama Rumah: "Blok A No. 12"
                $generatedHouseName = "Blok " . $block . " No. " . $number;

                // Cek Duplikasi (Opsional: Agar tidak ada rumah ganda)
                // $exist = House::where('house_name', $generatedHouseName)->first();
                // if($exist) ... throw error ...

                $house = House::create([
                    'house_name' => $generatedHouseName,
                    'owner_name' => $request->full_name, // Pemilik sesuai pendaftar

                    // Simpan detail alamat jalan di kolom address
                    // Atau mau digabung? Terserah kebutuhan.
                    // Disini saya simpan jalannya saja, karena blok/no sudah ada di house_name
                    'address'    => $street,

                    'status'     => 'occupied',
                    'house_type' => 'Unofficial',
                ]);

                $houseId = $house->id;
            }

            // --- 4. Handle Family ---
            $family = Family::create([
                'house_id'         => $houseId,
                'kk_number'        => $validated['kk_number'] ?? null,
                'ownership_status' => $validated['ownership_status'] ?? 'owner',
                'status'           => 'active'
            ]);

            // --- 5. Handle Citizen ---
            Citizen::create([
                'user_id'       => $user->id,
                'family_id'     => $family->id,
                'nik'           => $validated['nik'],
                'name'          => $validated['full_name'],
                'phone'         => $validated['phone'],
                'gender'        => $validated['gender'],
                'birth_place' => $request->birth_place,
                'birth_date'  => $request->birth_date,
                'religion'    => $request->religion,
                'blood_type'  => $request->blood_type,
                'education'     => $request->education,  // <--- Tambahkan ini
                'occupation'    => $request->occupation,
                'id_card_photo' => $ktpPath,
                'verified_selfie_photo' => $selfiePath,
                'family_role'   => 'Kepala Keluarga',
                'status'        => 'permanent'
            ]);

            // --- 6. Token Response ---
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
                'message' => 'Register berhasil.',
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
