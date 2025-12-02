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
        $citizen = Citizen::select('id', 'name')
            ->orderBy('name')
            ->get();

        // return response()->json($citizen);
        return response()->json([
            'status'  => 'success',
            'message' => 'Citizens retrieved successfully',
            'data'    => $citizen
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): jsonResponse
    {
        $validated = $request->validate([
            'family_id' => 'required|exists:families,id',
            'user_id' => 'required|exists:users,id',
            'nik' => 'required|string|unique:citizens,nik',
            'name' => 'required|string',
            'phone' => 'nullable|string',
            'birth_place' => 'required|string',
            'birth_date' => 'required|date',
            'gender' => 'required|string',
            'religion' => 'nullable|string',
            'blood_type' => 'nullable|string',
            'id_card_photo' => 'nullable|string',
            'family_role' => 'required|string',
            'education' => 'nullable|string',
            'occupation' => 'nullable|string',
            'status' => 'nullable|string'
        ]);

        $citizen = Citizen::create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'citizen added successfully',
            'data'    => $citizen
        ], 201);
    }
}
