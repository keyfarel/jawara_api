<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\GenerateReportRequest;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    /**
     * H.1 Semua Pemasukan
     * Data: Judul, Kategori, Tanggal, Nominal
     */
    public function incomes(): JsonResponse
    {
        return $this->getTransactionsByType('income');
    }

    /**
     * H.2 Semua Pengeluaran
     * Data: Judul, Kategori, Tanggal, Nominal
     */
    public function expenses(): JsonResponse
    {
        return $this->getTransactionsByType('expense');
    }

    /**
     * Helper Private agar tidak duplikasi kode
     */
    private function getTransactionsByType($type): JsonResponse
    {
        $transactions = Transaction::with('category') // Load nama kategori
        ->where('type', $type)
            ->orderBy('transaction_date', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id'       => $item->id,
                    'title'    => $item->title,
                    // Ambil nama kategori, jika null (misal iuran otomatis) beri default
                    'category' => $item->category ? $item->category->name : 'Umum/Tagihan',
                    'date'     => $item->transaction_date,
                    'amount'   => (int) $item->amount, // Cast ke integer biar rapi
                    'image'    => $item->proof_image_link
                ];
            });

        return response()->json([
            'status' => 'success',
            'data'   => $transactions
        ]);
    }

    /**
     * H.3 Cetak Laporan (Rekap Data)
     * Input: tanggal mulai, tanggal akhir, jenis laporan
     */
    public function report(GenerateReportRequest $request): JsonResponse
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

        // Hitung Total di Server biar Frontend tinggal tampilkan
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
            'data' => $transactions->map(function ($item) {
                return [
                    'date'     => $item->transaction_date,
                    'type'     => $item->type, // income/expense
                    'category' => $item->category ? $item->category->name : 'Lainnya',
                    'title'    => $item->title,
                    'amount'   => (int) $item->amount,
                ];
            })
        ]);
    }
}
