<?php

namespace App\Http\Controllers\Dosen;

use App\Models\Dosen;
use App\Models\User;
use Inertia\Inertia;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{

    public function getProfile()
    {
        $user = Auth::user();

        $dosen = Dosen::with([
            'user',
            'major',
        ])
            ->where('user_id', $user->id)
            ->first();

        if (!$dosen) {
            return response()->json(['message' => 'Data Dosen tidak ditemukan.'], 404);
        }

        $photoUrl = $dosen->user->photo ? url('https://polban-space.cloudias79.com/sispa/storage/profile_photos/KD5yW7wPe2L4q2HsBEgzh7UVLmFaYYDH59nz3G6F.png' . $dosen->user->photo) : null;

        return response()->json([
            'nama' => $dosen->user->name,
            'nip' => $dosen->nip,
            'kode_dosen' => $dosen->kode_dosen,
            'jurusan' => $dosen->major->major_name,
            'email' => $dosen->user->email,
            'telepon' => $dosen->phone,
            'photo' => $photoUrl, 
        ]);
    }

    public function profile()
    {
        return Inertia::render('Dosen/Profile');
    }

    // Upload foto profil
    public function uploadProfilePhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpg,png,jpeg,gif',
        ]);

        $user = Auth::user();
        $dosen = Dosen::where('user_id', $user->id)->first();

        if (!$dosen) {
            return response()->json(['message' => 'Dosen tidak ditemukan.'], 404);
        }

        // Hapus foto lama jika ada
        if ($dosen->user->photo) {
            Storage::delete($dosen->user->photo);
        }

        // Upload foto baru
        $path = $request->file('photo')->store('profile_photos', 'public');

        // Simpan path foto ke database
        $dosen->user->photo = $path;
        $dosen->user->save();

        return response()->json(['message' => 'Foto profil berhasil diupload.', 'path' => $path]);
    }

    // Delete foto profil
    public function deleteProfilePhoto()
    {
        $user = Auth::user();
        $dosen = Dosen::where('user_id', $user->id)->first();

        if (!$dosen || !$dosen->user->photo) {
            return response()->json(['message' => 'Tidak ada foto profil yang dapat dihapus.'], 404);
        }

        // Hapus foto dari storage
        Storage::delete($dosen->user->photo);

        // Hapus path foto dari database
        $dosen->user->photo = null;
        $dosen->user->save();

        return response()->json(['message' => 'Foto profil berhasil dihapus.']);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $dosen = Dosen::where('user_id', $user->id)->first();

        if (!$dosen) {
            return response()->json(['message' => 'Data Dosen tidak ditemukan.'], 404);
        }

        // Validasi input yang diterima
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'nip' => 'required|string|max:21',
            'kode_dosen' => 'required|string|max:255',
            'jurusan' => 'required|string|max:255',
            'email' => 'required|email',
            'telepon' => 'nullable|string|max:15', // Pastikan Anda memvalidasi telepon
        ]);

        // Update data dosen dengan data yang valid
        $dosen->user->name = $validated['nama'];
        $dosen->nip = $validated['nip'];
        $dosen->kode_dosen = $validated['kode_dosen'];
        $dosen->major->major_name = $validated['jurusan'];
        $dosen->user->email = $validated['email'];
        $dosen->phone = $validated['telepon'];
        $dosen->user->save();
        $dosen->save();

        return response()->json(['message' => 'Profile updated successfully.']);
    }

}
