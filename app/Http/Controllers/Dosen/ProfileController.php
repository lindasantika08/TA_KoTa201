<?php

namespace App\Http\Controllers\Dosen;

use App\Models\Dosen;
use Inertia\Inertia;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{

    public function getProfile()
    {
        // Ambil data Dosen berdasarkan user yang sedang login
        $user = Auth::user(); // Ambil user yang sedang login

        // Ambil data Dosen yang terkait dengan user
        $dosen = Dosen::with([
            'user',          // Relasi dengan tabel user
            'major', // Relasi dengan major
        ])
            ->where('user_id', $user->id) // Pastikan hanya mengambil data Dosen yang sesuai dengan user yang sedang login
            ->first(); // Ambil hanya satu data Dosen (karena user hanya punya satu Dosen)

        if (!$dosen) {
            return response()->json(['message' => 'Data Dosen tidak ditemukan.'], 404);
        }

        // Periksa apakah Dosen memiliki foto dan buat URL dengan asset()
        $photoUrl = $dosen->user->photo ? asset('storage/' . $dosen->user->photo) : null;

        // Kembalikan data Dosen dengan relasi terkait
        return response()->json([
            'nama' => $dosen->user->name,
            'nip' => $dosen->nip,
            'jurusan' => $dosen->major->major_name,
            'email' => $dosen->user->email,
            'telepon' => $dosen->phone, // Misalkan ada kolom telepon di tabel user
            'photo' => $photoUrl, // Menambahkan URL foto
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
            'jurusan' => 'required|string|max:255',
            'email' => 'required|email',
            'telepon' => 'nullable|string|max:15', // Pastikan Anda memvalidasi telepon
        ]);

        // Update data dosen dengan data yang valid
        $dosen->user->name = $validated['nama'];
        $dosen->nip = $validated['nip'];
        $dosen->major->major_name = $validated['jurusan'];
        $dosen->user->email = $validated['email'];
        $dosen->phone = $validated['telepon'];
        $dosen->user->save();
        $dosen->save();

        return response()->json(['message' => 'Profile updated successfully.']);
    }
}
