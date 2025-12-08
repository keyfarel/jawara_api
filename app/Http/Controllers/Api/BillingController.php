<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Billing;
use App\Http\Requests\StoreBillingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BillingController extends Controller
{
    /**
     * GET /api/billings
     * Menampilkan semua data tagihan
     */
    public function index()
    {
        $billings = Billing::with(['family', 'duesType'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'List Data Tagihan',
            'data'    => $billings
        ], 200);
    }

    /**
     * POST /api/billings
     * Membuat Tagihan Baru (Fitur Tagih Iuran)
     */
    public function store(StoreBillingRequest $request)
    {
        // Generate kode unik, misal: INV-TIMESTAMP-RANDOM
        $billingCode = 'INV-' . time() . '-' . strtoupper(Str::random(4));

        // Create Data
        $billing = Billing::create([
            'family_id'    => $request->family_id,
            'dues_type_id' => $request->dues_type_id,
            'billing_code' => $billingCode,
            'period'       => $request->period,
            'amount'       => $request->amount,
            'status'       => $request->status ?? 'unpaid', // Default unpaid
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tagihan berhasil dibuat',
            'data'    => $billing
        ], 201);
    }

    /**
     * GET /api/billings/{id}
     * Detail satu tagihan
     */
    public function show($id)
    {
        $billing = Billing::with(['family', 'duesType'])->find($id);

        if (!$billing) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $billing
        ], 200);
    }

    /**
     * PUT /api/billings/{id}
     * Update tagihan (misal update status bayar atau jumlah)
     */
    public function update(Request $request, $id)
    {
        $billing = Billing::find($id);

        if (!$billing) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        // Validasi sederhana untuk update
        $request->validate([
            'amount' => 'numeric',
            'status' => 'in:paid,unpaid',
            'period' => 'string'
        ]);

        $billing->update($request->only(['amount', 'status', 'period', 'dues_type_id']));

        return response()->json([
            'success' => true,
            'message' => 'Tagihan berhasil diupdate',
            'data'    => $billing
        ], 200);
    }

    /**
     * DELETE /api/billings/{id}
     * Hapus tagihan
     */
    public function destroy($id)
    {
        $billing = Billing::find($id);

        if (!$billing) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        $billing->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tagihan berhasil dihapus'
        ], 200);
    }
}