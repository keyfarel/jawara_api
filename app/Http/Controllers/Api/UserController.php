<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * B.1 List Pengguna
     * Data: nama, email, status registrasi
     */
    public function index(): JsonResponse
    {
        // Mengambil semua user
        $users = User::select('id', 'name', 'email', 'registration_status', 'role')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $users
        ]);
    }

    /**
     * B.2 Tambah Pengguna (Oleh Admin)
     * Data: nama, email, hp, password, role
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Buat User Baru
        $user = User::create([
            'name'                => $validated['name'],
            'email'               => $validated['email'],
            'phone'               => $validated['phone'],
            'password'            => Hash::make($validated['password']), // Hash password
            'role'                => $validated['role'],
            'registration_status' => 'verified', // Auto verified karena dibuat admin
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'User created successfully',
            'data'    => $user
        ], 201);
    }

    public function update(UpdateUserRequest $request, $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        $validated = $request->validated();

        // Data yang akan diupdate
        $dataToUpdate = [
            'name'  => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'role'  => $validated['role'],
        ];

        // Cek apakah password diisi? Jika ya, hash dan masukkan.
        // Jika tidak (null), biarkan password lama.
        if (!empty($validated['password'])) {
            $dataToUpdate['password'] = Hash::make($validated['password']);
        }

        $user->update($dataToUpdate);

        return response()->json([
            'status'  => 'success',
            'message' => 'User updated successfully',
            'data'    => $user
        ]);
    }

    /**
     * B.4 Hapus Pengguna
     * Method: DELETE
     */
    public function destroy($id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        // Opsional: Cegah Admin menghapus dirinya sendiri saat sedang login
        if (auth()->id() == $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot delete your own account'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'User deleted successfully'
        ]);
    }

    public function options(): JsonResponse
    {
        $users = User::doesntHave('citizen')
            ->select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json($users);
    }
}
