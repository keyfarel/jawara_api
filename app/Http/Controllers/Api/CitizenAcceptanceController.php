<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Resources\CitizenListResource;
use App\Models\Citizen;
use Illuminate\Http\Request;

class CitizenAcceptanceController extends Controller
{
    public function index(Request $request)
    {
        $query = Citizen::with('user'); 

        if ($request->has('status')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('registration_status', $request->status);
            });
        }

        $citizens = $query->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'List data warga berhasil diambil',
            'data' => CitizenListResource::collection($citizens)
        ]);
    }
}