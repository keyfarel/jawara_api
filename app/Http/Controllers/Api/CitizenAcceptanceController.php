<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CitizenListResource;
use App\Models\Citizen;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CitizenAcceptanceController extends Controller
{
    /**
     * 1. List Data Warga (Filter by Status)
     * GET /api/citizens/verification-list
     */
    public function index(Request $request): JsonResponse
    {
        $query = Citizen::with(['user', 'family.house']); // Load relasi user & rumah

        // Filter berdasarkan status registrasi user (pending, verified, rejected)
        if ($request->has('status')) {
            $status = $request->status;
            $query->whereHas('user', function($q) use ($status) {
                $q->where('registration_status', $status);
            });
        }

        // Default urutkan dari yang terbaru
        $citizens = $query->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'List data warga berhasil diambil',
            'data'    => CitizenListResource::collection($citizens)
        ]);
    }

    /**
     * 2. Detail Warga
     * GET /api/citizens/verification-list/{id}
     */
    public function show($id): JsonResponse
    {
        $citizen = Citizen::with(['user', 'family.house'])->find($id);

        if (!$citizen) {
            return response()->json([
                'success' => false,
                'message' => 'Data warga tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail warga berhasil diambil',
            'data'    => new CitizenListResource($citizen)
        ]);
    }

    /**
     * 3. Verifikasi Warga (Terima / Tolak)
     * PUT /api/citizens/verification-list/{id}
     * Body: { "status": "verified" } atau { "status": "rejected" }
     */
    public function update(Request $request, $id): JsonResponse
    {
        // Validasi input status
        $request->validate([
            'status' => 'required|in:verified,rejected,pending'
        ]);

        $citizen = Citizen::with('user')->find($id);

        if (!$citizen) {
            return response()->json([
                'success' => false,
                'message' => 'Data warga tidak ditemukan',
            ], 404);
        }

        return DB::transaction(function () use ($citizen, $request) {
            $newStatus = $request->status;

            // 1. Update Status di Tabel Users (Untuk Login)
            if ($citizen->user) {
                $citizen->user->update([
                    'registration_status' => $newStatus
                ]);
            }

            // 2. Update Status di Tabel Citizens (Data Kependudukan)
            // Jika verified -> status warga jadi 'permanent' (atau 'active')
            // Jika rejected -> status warga jadi 'inactive' (atau dihapus soft delete)
            if ($newStatus === 'verified') {
                $citizen->update(['status' => 'permanent']);
            } elseif ($newStatus === 'rejected') {
                $citizen->update(['status' => 'inactive']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Status verifikasi berhasil diperbarui menjadi ' . $newStatus,
                'data'    => new CitizenListResource($citizen->refresh())
            ]);
        });
    }

    /**
     * 4. Hapus Data Pengajuan (Hard Delete)
     * DELETE /api/citizens/verification-list/{id}
     */
    public function destroy($id): JsonResponse
    {
        $citizen = Citizen::with('user')->find($id);

        if (!$citizen) {
            return response()->json([
                'success' => false,
                'message' => 'Data warga tidak ditemukan',
            ], 404);
        }

        return DB::transaction(function () use ($citizen) {
            // Hapus User terkait jika ada (Opsional, tergantung kebijakan)
            // Biasanya kalau data warga dihapus saat fase pending, akunnya juga dihapus
            if ($citizen->user) {
                $citizen->user->delete();
            }

            // Hapus data citizen
            $citizen->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data pengajuan warga berhasil dihapus permanen',
            ]);
        });
    }
}
