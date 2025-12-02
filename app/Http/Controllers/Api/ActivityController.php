<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Activity\StoreActivityRequest; // <--- Import ini
use App\Models\Activity;
use Illuminate\Http\JsonResponse;

class ActivityController extends Controller
{
    /**
     * G.1 List Kegiatan
     * Data: nama, tanggal, lokasi, status
     * (Ditambah deskripsi & kategori agar lengkap untuk detail)
     */
    public function index(): JsonResponse
    {
        $activities = Activity::orderBy('activity_date', 'desc')
            ->select([
                'id',
                'name',
                'activity_date',
                'location',         // <--- Baru (Sesuai G.1)
                'status',
                'category',         // Opsional tapi berguna
                'person_in_charge', // Opsional
                'description'       // Opsional
            ])
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $activities
        ]);
    }

    /**
     * G.2 Tambah Kegiatan
     * Data: nama, kategori, tanggal, lokasi, penanggung jawab, deskripsi
     */
    public function store(StoreActivityRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Set default status jika tidak ada input status
        // Misalnya: 'scheduled' (dijadwalkan) atau 'pending'
        $validated['status'] = 'upcoming';

        $activity = Activity::create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Activity created successfully',
            'data'    => $activity
        ], 201);
    }
}
