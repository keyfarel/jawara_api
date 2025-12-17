<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TransactionCategory;
use Illuminate\Http\JsonResponse;

class TransactionCategoriesController extends Controller
{
    public function option(): JsonResponse
    {
        $categories = TransactionCategory::all(['id', 'name']);
        
        return response()->json([
            'status' => 'success',
            'data' => $categories
        ]);
    }
}
