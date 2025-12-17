<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\House;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class HouseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): jsonResponse
    {
        $houses = House::orderBy('house_name')->get();

        // return response()->json($houses);
        return response()->json([
            'status'  => 'success',
            'message' => 'Houses retrieved successfully',
            'data'    => $houses
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): jsonResponse
    {
        $validated = $request->validate([
            'house_name' => 'nullable|string', //alamat rumah
            'owner_name' => 'required|string', // nama pemilik
            'address' => 'required|string', // alamat lengkap
            'house_type' => 'required', // tipe rumah
            'has_complete_facilities' => 'required|boolean', // fasilitas lengkap atau tidak
            'status' => 'nullable|string'
        ]);

        if(!$request['house_name']){
            $request['house_name'] = $request['address'];
        }

        $house = House::create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'House created successfully',
            'data'    => $house
        ], 201);
    }

    public function options()
    {
        $houses = House::select('id', 'house_name')
            ->orderBy('house_name')
            ->get();

        return response()->json($houses);
    }
}
