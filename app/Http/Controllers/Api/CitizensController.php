<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Citizen;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CitizensController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): jsonResponse
    {
        // UBAH DISINI: dari 'house' menjadi 'family.house'
        // Artinya: Load Family dulu, lalu dari Family load House
        $citizens = Citizen::with('family.house')
            ->orderBy('name')
            ->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'Citizens retrieved successfully',
            'data'   => $citizens
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        // 1. Validasi Input
        $validated = $request->validate([
            'family_id'     => 'required|exists:families,id',
            'user_id'       => 'nullable|exists:users,id',
            'nik'           => 'required|string|unique:citizens,nik',
            'name'          => 'required|string',
            'phone'         => 'nullable|string',
            'birth_place'   => 'required|string',
            'birth_date'    => 'required|date',
            'gender'        => 'required|string', // male, female
            'religion'      => 'nullable|string',
            'blood_type'    => 'nullable|string',
            'id_card_photo' => 'nullable|string',
            'family_role'   => 'required|string', // husband, wife, etc
            'education'     => 'nullable|string',
            'occupation'    => 'nullable|string',
            'status'        => 'nullable|string'
        ]);

        // 2. Set Default Value (Logika Tambahan)
        // Jika null, isi dengan 'Lainnya'
        $validated['education']  = $validated['education'] ?? 'Lainnya';
        $validated['occupation'] = $validated['occupation'] ?? 'Lainnya';

        // Default status jika null
        $validated['status']     = $validated['status'] ?? 'active';

        // 3. Simpan ke Database
        $citizen = Citizen::create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Citizen added successfully',
            'data'    => $citizen
        ], 201);
    }
}
