<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Models\Citizen;
use App\Models\Family;
use App\Models\Activity;
use App\Models\Transaction;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function mainDashboard(): JsonResponse
    {
        $data = [
            'total population' => [
                'male population' => 1000, // laki - laki
                'female population' => 1000, // perempuan
            ], // total populasi
            'total families' => 500, // total keluarga
            'total activity' => [
                'completed' => 120,
                'upcoming' => 30,
                'ongoing' => 10,
            ], // total aktivitas
            'cash' => [
                'total cash' => 7500000, // total kas
                'income' => 5000000, // total pemasukan
                'expense' => 2500000, // total pengeluaran
            ],
            'new activity' => ['activity1', 'activity2', 'activity3']
        ];

        return response()->json($data);
    }

    public function financeDashboard(): JsonResponse
    {
        // Logic to gather finance dashboard data
        $data = [

        // Total Income & Expense
        'total_income' => Transaction::where('type', 'income')->sum('amount'),
        'total_expense' => Transaction::where('type', 'expense')->sum('amount'),

        // Cash balance
        'total_cash' => Transaction::where('type', 'income')->sum('amount')
                        - Transaction::where('type', 'expense')->sum('amount'),

        // transaction count
        'transactions' => Transaction::count(),

        // Income per month chart
        'income_per_month' => Transaction::where('type', 'income')
            ->selectRaw("MONTH(transaction_date) month, SUM(amount) total")
            ->groupByRaw("MONTH(transaction_date)")
            ->pluck('total', 'month')
            ->mapWithKeys(fn($total, $m) => [
                Carbon::create()->month($m)->format('F') => $total
            ]),

        // Expense per month chart
        'expense_per_month' => Transaction::where('type', 'expense')
            ->selectRaw("MONTH(transaction_date) month, SUM(amount) total")
            ->groupByRaw("MONTH(transaction_date)")
            ->pluck('total', 'month')
            ->mapWithKeys(fn($total, $m) => [
                Carbon::create()->month($m)->format('F') => $total
            ]),

        // Income per category
        'income_per_category' => Transaction::where('type', 'income')
            ->selectRaw("transaction_category_id, SUM(amount) total")
            ->groupBy('transaction_category_id')
            ->pluck('total', 'transaction_category_id'),

        // Expense per category
        'expense_per_category' => Transaction::where('type', 'expense')
            ->selectRaw("transaction_category_id, SUM(amount) total")
            ->groupBy('transaction_category_id')
            ->pluck('total', 'transaction_category_id'),
        ];

        return response()->json($data);
    }

    public function activityDashboard(): JsonResponse
    {
        // Logic to gather activity dashboard data
        $now = now();

        $data = [
            // Total semua kegiatan
            'total_activities' => Activity::count(),

            // Berdasarkan status
            'upcoming_activities' => Activity::where('status', 'upcoming')->count(),
            'ongoing_activities' => Activity::where('status', 'ongoing')->count(),
            'complete_activities' => Activity::where('status', 'completed')->count(),
            'cancelled_activities' => Activity::where('status', 'cancelled')->count(),

            // Distribusi kategori
            'type_of_activity' => Activity::selectRaw('category, COUNT(*) AS total')
                ->groupBy('category')
                ->pluck('total', 'category'),

            // Jumlah aktivitas per bulan
            'activities_per_month' => Activity::selectRaw('MONTH(activity_date) AS month, COUNT(*) AS total')
                ->groupByRaw('MONTH(activity_date)')
                ->pluck('total', 'month')
                ->mapWithKeys(fn ($total, $month) => [
                    Carbon::create()->month($month)->format('F') => $total
                ]),
        ];

        return response()->json($data);
    }

    public function populationDashboard(): JsonResponse
    {
        // Logic to gather population dashboard data
        $data = [
            'total_families' => Family::count(),

            'total_population' => Citizen::count(),

            // 'family_status_distribution' => Family::selectRaw('status, COUNT(*) total')
            //     ->groupBy('status')
            //     ->pluck('total', 'status'),

            // 'ownership_status_distribution' => Family::selectRaw('ownership_status, COUNT(*) total')
            //     ->groupBy('ownership_status')
            //     ->pluck('total', 'ownership_status'),

            // 'population_by_house' => Family::withCount('citizens')
            //     ->pluck('citizens_count', 'house_id'),

            'status_distribution' => Citizen::selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),

            'gender_distribution' => Citizen::selectRaw('gender, COUNT(*) as total')
                ->groupBy('gender')
                ->pluck('total', 'gender'),

            'roles_distribution' => Citizen::selectRaw('family_role, COUNT(*) as total')
                ->groupBy('family_role')
                ->pluck('total', 'family_role'),

            'religion_distribution' => Citizen::selectRaw('religion, COUNT(*) as total')
                ->groupBy('religion')
                ->pluck('total', 'religion'),

            'education_level_distribution' => Citizen::selectRaw('education, COUNT(*) as total')
                ->groupBy('education')
                ->pluck('total', 'education'),

            'occupation_distribution' => Citizen::selectRaw('occupation, COUNT(*) as total')
                ->groupBy('occupation')
                ->pluck('total', 'occupation'),
        ];

        return response()->json($data);
    }
}
