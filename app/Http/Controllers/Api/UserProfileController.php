<?php

// FILE: app/Http/Controllers/Api/UserProfileController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserProfileController extends Controller
{
    /**
     * Mengembalikan data profil pengguna yang sedang login.
     */
    public function show()
    {
        try {
            // Mengambil objek user yang sedang terautentikasi
            $user = Auth::user();

            // Memastikan user tidak null
            if (! $user) {
                return response()->json(['message' => 'User not authenticated'], 401);
            }

            // Kembalikan hanya atribut-atribut yang spesifik dan dibutuhkan
            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'id_karyawan' => $user->id_karyawan,
                'departemen' => $user->departemen,
                'profile_photo_path' => $user->profile_photo_path,
                'profile_photo_url' => $user->profilePhotoDisplayUrl(),
            ]);

        } catch (\Exception $e) {
            // Menangkap error tak terduga
            return response()->json([
                'message' => 'An unexpected error occurred.',
                'error' => config('app.debug') ? $e->getMessage() : 'Silakan hubungi Admin.',
            ], 500);
        }
    }

    /**
     * Memperbarui data profil pengguna (nama, departemen, dan foto).
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        // Validasi input
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'id_karyawan' => 'prohibited',
            'departemen' => 'sometimes|required|string|max:255',
            'profile_photo' => 'nullable|image|max:2048', // Maksimal 2MB (Cegah Bom Penyimpanan)
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        if ($validated === []) {
            return response()->json([
                'errors' => ['profile' => ['Tambahkan setidaknya satu perubahan profil.']],
            ], 422);
        }

        $previousPhotoPath = $user->profile_photo_path;
        $newPhotoPath = null;

        if ($request->hasFile('profile_photo')) {
            $newPhotoPath = $request->file('profile_photo')->store('profile-photos', 'public');
            if (! $newPhotoPath) {
                throw new \RuntimeException('Foto profil gagal disimpan.');
            }
        }

        try {
            DB::transaction(function () use ($user, $validated, $newPhotoPath): void {
                if (array_key_exists('name', $validated)) {
                    $user->name = $validated['name'];
                }

                if (array_key_exists('departemen', $validated)) {
                    $user->departemen = $validated['departemen'];
                }

                if ($newPhotoPath) {
                    $user->profile_photo_path = $newPhotoPath;
                    $user->profile_photo_url = $user->profilePhotoDisplayUrl();
                }

                $user->save();
            });
        } catch (\Throwable $exception) {
            if ($newPhotoPath) {
                Storage::disk('public')->delete($newPhotoPath);
            }

            throw $exception;
        }

        if ($newPhotoPath && $previousPhotoPath && $previousPhotoPath !== $newPhotoPath) {
            Storage::disk('public')->delete($previousPhotoPath);
        }

        $updatedUser = $user->fresh();
        $updatedUser->profile_photo_url = $updatedUser->profilePhotoDisplayUrl();

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user' => $updatedUser,
        ]);
    }
}
