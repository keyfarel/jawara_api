<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\GenerateReportRequest;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    /**
     * 1. GET: List Semua Pemasukan Lain
     */
    public function indexIncome()
    {
        // Ambil semua transaksi dengan type 'income'
        $incomes = Transaction::with('category') // Eager load kategori agar efisien
            ->where('type', 'income')
            ->orderBy('transaction_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'List Data Pemasukan Lain',
            'data'    => $incomes
        ], 200);
    }

    /**
     * 2. GET: Detail Pemasukan Lain (By ID)
     */
    public function showIncome($id)
    {
        // Cari transaksi berdasarkan ID dan pastikan type-nya 'income'
        $income = Transaction::with('category')->where('type', 'income')->find($id);

        if (!$income) {
            return response()->json([
                'success' => false,
                'message' => 'Data pemasukan tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail Data Pemasukan',
            'data'    => $income
        ], 200);
    }

    /**
     * 3. POST: Tambah Pemasukan Baru
     */
    public function storeIncome(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'                   => 'required|string|max:255',
            'transaction_date'        => 'required|date',
            'transaction_category_id' => 'required|exists:transaction_categories,id',
            'amount'                  => 'required|numeric|min:0',
            'description'             => 'nullable|string',
            'proof_image'             => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi Gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Upload Gambar
        $imagePath = null;
        if ($request->hasFile('proof_image')) {
            $imagePath = $request->file('proof_image')->store('proofs', 'public');
        }

        $transaction = Transaction::create([
            'user_id'                 => auth()->id(),
            'transaction_category_id' => $request->transaction_category_id,
            'billing_id'              => null,
            'title'                   => $request->title,
            'type'                    => 'income',
            'amount'                  => $request->amount,
            'transaction_date'        => $request->transaction_date,
            'description'             => $request->description,
            'proof_image'             => $imagePath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pemasukan berhasil ditambahkan',
            'data'    => $transaction
        ], 201);
    }

    /**
     * 4. PUT: Update Pemasukan
     * Catatan: Untuk update gambar via API, Client harus menggunakan method POST
     * dengan field _method = PUT di body.
     */
    public function updateIncome(Request $request, $id)
    {
        // Cari data
        $transaction = Transaction::where('type', 'income')->find($id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Data pemasukan tidak ditemukan',
            ], 404);
        }

        // Validasi (Gambar tidak wajib / nullable saat update)
        $validator = Validator::make($request->all(), [
            'title'                   => 'required|string|max:255',
            'transaction_date'        => 'required|date',
            'transaction_category_id' => 'required|exists:transaction_categories,id',
            'amount'                  => 'required|numeric|min:0',
            'description'             => 'nullable|string',
            'proof_image'             => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi Gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        // Cek jika ada upload gambar baru
        if ($request->hasFile('proof_image')) {
            // Hapus gambar lama jika ada
            if ($transaction->proof_image && Storage::disk('public')->exists($transaction->proof_image)) {
                Storage::disk('public')->delete($transaction->proof_image);
            }
            // Simpan gambar baru
            $imagePath = $request->file('proof_image')->store('proofs', 'public');
            $transaction->proof_image = $imagePath;
        }

        // Update Data Lainnya
        $transaction->title                   = $request->title;
        $transaction->transaction_date        = $request->transaction_date;
        $transaction->transaction_category_id = $request->transaction_category_id;
        $transaction->amount                  = $request->amount;
        $transaction->description             = $request->description;

        $transaction->save();

        return response()->json([
            'success' => true,
            'message' => 'Data pemasukan berhasil diperbarui',
            'data'    => $transaction
        ], 200);
    }

    /**
     * 5. DELETE: Hapus Pemasukan
     */
    public function destroyIncome($id)
    {
        $transaction = Transaction::where('type', 'income')->find($id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Data pemasukan tidak ditemukan',
            ], 404);
        }

        // Hapus file gambar dari storage
        if ($transaction->proof_image && Storage::disk('public')->exists($transaction->proof_image)) {
            Storage::disk('public')->delete($transaction->proof_image);
        }

        // Hapus data dari database
        $transaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data pemasukan berhasil dihapus',
        ], 200);
    }

    /**
     * H.3 Cetak Laporan (Rekap Data)
     * Mengembalikan data transaksi berdasarkan rentang tanggal dan tipe.
     */
    public function report(\App\Http\Requests\Transaction\GenerateReportRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $startDate = $validated['start_date'];
        $endDate   = $validated['end_date'];
        $filterType = $validated['type'] ?? 'all'; // Default ambil semua

        // Query Dasar
        $query = Transaction::with('category')
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        // Jika user memilih filter spesifik (hanya income saja / expense saja)
        if ($filterType !== 'all') {
            $query->where('type', $filterType);
        }

        $transactions = $query->orderBy('transaction_date', 'asc')->get();

        // Hitung Total di Server
        $totalIncome = $transactions->where('type', 'income')->sum('amount');
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');

        return response()->json([
            'status' => 'success',
            'meta'   => [
                'period' => "$startDate s/d $endDate",
                'total_income' => (int) $totalIncome,
                'total_expense' => (int) $totalExpense,
                'net_balance'  => (int) ($totalIncome - $totalExpense) // Saldo Bersih
            ],
            'data' => $transactions
        ]);
    }
}
