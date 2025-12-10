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
        // Population
        $malePopulation   = Citizen::where('gender', 'male')->count();
        $femalePopulation = Citizen::where('gender', 'female')->count();

        // Families
        $totalFamilies = Family::count();

        // Activity Status
        $completedActivities = Activity::where('status', 'completed')->count();
        $upcomingActivities  = Activity::where('status', 'upcoming')->count();
        $ongoingActivities   = Activity::where('status', 'ongoing')->count();

        // Finance
        $totalIncome  = Transaction::where('type', 'income')->sum('amount');
        $totalExpense = Transaction::where('type', 'expense')->sum('amount');
        $totalCash    = $totalIncome - $totalExpense;

        // Latest 5 activities
        $latestActivities = Activity::orderBy('activity_date', 'desc')
            ->limit(5)
            ->pluck('name'); // array string nama activity

        $data = [
            'total_population' => [
                'male_population'   => $malePopulation,
                'female_population' => $femalePopulation,
            ],
            'total_families' => $totalFamilies,
            'total_activity' => [
                'completed' => $completedActivities,
                'upcoming'  => $upcomingActivities,
                'ongoing'   => $ongoingActivities,
            ],
            'cash' => [
                'total_cash' => $totalCash,
                'income'     => $totalIncome,
                'expense'    => $totalExpense,
            ],
            'new_activity' => $latestActivities,
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
