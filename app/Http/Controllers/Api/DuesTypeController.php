<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DuesType;
use App\Http\Requests\StoreDuesTypeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DuesTypeController extends Controller
{
    // GET: Tampilkan semua jenis iuran
    public function index()
    {
        $duesTypes = DuesType::latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'List Data Kategori Iuran',
            'data'    => $duesTypes
        ], 200);
    }

    // POST: Simpan jenis iuran baru
    public function store(StoreDuesTypeRequest $request)
    {
        $duesType = DuesType::create([
            'name'   => $request->name,
            'amount' => $request->amount,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori Iuran Berhasil Ditambahkan',
            'data'    => $duesType
        ], 201);
    }

    // GET: Detail satu iuran
    public function show($id)
    {
        $duesType = DuesType::find($id);

        if (!$duesType) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail Kategori Iuran',
            'data'    => $duesType
        ], 200);
    }

    // PUT: Update jenis iuran
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name'   => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $duesType = DuesType::find($id);

        if (!$duesType) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $duesType->update([
            'name'   => $request->name,
            'amount' => $request->amount,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori Iuran Berhasil Diupdate',
            'data'    => $duesType
        ], 200);
    }

    // DELETE: Hapus jenis iuran
    public function destroy($id)
    {
        $duesType = DuesType::find($id);

        if (!$duesType) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $duesType->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori Iuran Berhasil Dihapus',
        ], 200);
    }
}