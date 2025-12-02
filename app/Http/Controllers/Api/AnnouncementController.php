<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Announcement\StoreAnnouncementRequest;
use App\Models\Announcement;
use Illuminate\Http\JsonResponse;

class AnnouncementController extends Controller
{
    /**
     * G.3 List Broadcast
     * Data: Pengirim, Judul, Tanggal
     */
    public function index(): JsonResponse
    {
        // Load relasi user, ambil namanya saja
        $announcements = Announcement::with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'id'            => $item->id,
                    'title'         => $item->title,
                    'content'       => $item->content, // Opsional untuk preview
                    'sender_name'   => $item->user->name, // Nama Pengirim
                    'created_at'    => $item->created_at->format('Y-m-d H:i'), // Tanggal diformat
                    // Accessor otomatis muncul (image_link & document_link)
                    'image_link'    => $item->image_link,
                    'document_link' => $item->document_link,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data'   => $announcements
        ]);
    }

    /**
     * G.4 Tambah Broadcast
     * Data: Judul, Isi, Foto, Dokumen
     */
    public function store(StoreAnnouncementRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // 1. Upload Gambar (Jika ada)
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('announcements/images', 'public');
        }

        // 2. Upload Dokumen (Jika ada)
        $documentPath = null;
        if ($request->hasFile('document')) {
            $documentPath = $request->file('document')->store('announcements/documents', 'public');
        }

        // 3. Simpan ke Database
        $announcement = Announcement::create([
            'user_id'      => auth()->id(), // Ambil ID user yang sedang login (token)
            'title'        => $validated['title'],
            'content'      => $validated['content'],
            'image_url'    => $imagePath,
            'document_url' => $documentPath,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Announcement broadcasted successfully',
            'data'    => $announcement
        ], 201);
    }
}
