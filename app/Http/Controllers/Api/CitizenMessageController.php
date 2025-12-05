<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CitizenMessage;
use App\Http\Requests\StoreAspirasiRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class CitizenMessageController extends Controller
{
    // GET: List Aspirasi
    public function index()
    {
        $user = Auth::user();

        // Opsional: Logika jika Admin bisa lihat semua, Warga hanya lihat miliknya
        if ($user->role === 'admin') {
            $messages = CitizenMessage::with('user:id,email,role')->latest()->get();
        } else {
            $messages = CitizenMessage::with('user:id,email')->where('user_id', $user->id)->latest()->get();
        }

        return response()->json([
            'success' => true,
            'message' => 'List Data Aspirasi',
            'data'    => $messages
        ]);
    }

    // POST: Buat Aspirasi Baru
    public function store(StoreAspirasiRequest $request)
    {
        // Ambil ID user dari Token JWT
        $userId = Auth::id();

        $aspirasi = CitizenMessage::create([
            'user_id'     => $userId,
            'title'       => $request->title,
            'description' => $request->description,
            'status'      => 'pending' // Default status
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Aspirasi berhasil dikirim',
            'data'    => $aspirasi
        ], 201);
    }

    // GET: Detail Aspirasi
    public function show($id)
    {
        $message = CitizenMessage::with('user:id,email,role')->find($id);

        if (!$message) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        // Opsional: Proteksi agar user A tidak bisa melihat aspirasi user B
        // if (Auth::user()->role !== 'admin' && $message->user_id !== Auth::id()) {
        //     return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        // }

        return response()->json([
            'success' => true,
            'message' => 'Detail Aspirasi',
            'data'    => $message
        ]);
    }

    // PUT: Update Aspirasi (Bisa untuk Admin update status, atau User edit konten)
    public function update(Request $request, $id)
    {
        $message = CitizenMessage::find($id);

        if (!$message) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        $user = Auth::user();

        // Skenario 1: Jika Admin, boleh update status
        if ($user->role === 'admin') {
            $request->validate(['status' => 'required|in:pending,process,done,rejected']);
            $message->update(['status' => $request->status]);
        } 
        // Skenario 2: Jika Pemilik, boleh update konten (selama belum diproses)
        elseif ($message->user_id === $user->id) {
            if ($message->status !== 'pending') {
                return response()->json(['success' => false, 'message' => 'Aspirasi yang sudah diproses tidak bisa diedit'], 400);
            }
            
            $request->validate([
                'title' => 'string|max:255',
                'description' => 'string'
            ]);

            $message->update($request->only(['title', 'description', 'date']));
        } 
        else {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Aspirasi berhasil diperbarui',
            'data'    => $message
        ]);
    }

    // DELETE: Hapus Aspirasi
    public function destroy($id)
    {
        $message = CitizenMessage::find($id);

        if (!$message) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        // Cek kepemilikan atau admin
        if (Auth::user()->role !== 'admin' && $message->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Aspirasi berhasil dihapus'
        ]);
    }
}