<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Models\Mahasiswa;
use Inertia\Inertia;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class ProfileMahasiswa extends Controller
{

    public function getProfile()
    {
        // Ambil data mahasiswa berdasarkan user yang sedang login
        $user = Auth::user(); // Ambil user yang sedang login

        // Ambil data mahasiswa yang terkait dengan user
        $mahasiswa = Mahasiswa::with([
            'user',          // Relasi dengan tabel user
            'classRoom.prodi.major', // Relasi dengan class room dan prodi serta major
        ])
            ->where('user_id', $user->id) // Pastikan hanya mengambil data mahasiswa yang sesuai dengan user yang sedang login
            ->first(); // Ambil hanya satu data mahasiswa (karena user hanya punya satu mahasiswa)

        if (!$mahasiswa) {
            return response()->json(['message' => 'Data mahasiswa tidak ditemukan.'], 404);
        }

        // Periksa apakah mahasiswa memiliki foto dan buat URL dengan asset()
        $photoUrl = $mahasiswa->user->photo ? asset('storage/' . $mahasiswa->user->photo) : null;

        // Kembalikan data mahasiswa dengan relasi terkait
        return response()->json([
            'nama' => $mahasiswa->user->name,
            'nim' => $mahasiswa->nim,
            'prodi' => $mahasiswa->classRoom->prodi->prodi_name,
            'jurusan' => $mahasiswa->classRoom->prodi->major->major_name,
            'email' => $mahasiswa->user->email,
            'telepon' => $mahasiswa->phone, // Misalkan ada kolom telepon di tabel user
            'photo' => $photoUrl, // Menambahkan URL foto
        ]);
    }

    public function profile()
    {
        return Inertia::render('Mahasiswa/Profile');
    }

    // Upload foto profil
    public function uploadProfilePhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpg,png,jpeg,gif|max:2048',
        ]);

        $user = Auth::user();
        $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

        if (!$mahasiswa) {
            return response()->json(['message' => 'Mahasiswa tidak ditemukan.'], 404);
        }

        // Hapus foto lama jika ada
        if ($mahasiswa->user->photo) {
            Storage::delete($mahasiswa->user->photo);
        }

        // Upload foto baru
        $path = $request->file('photo')->store('profile_photos', 'public');

        // Simpan path foto ke database
        $mahasiswa->user->photo = $path;
        $mahasiswa->user->save();

        return response()->json(['message' => 'Foto profil berhasil diupload.', 'path' => $path]);
    }

    // Delete foto profil
    public function deleteProfilePhoto()
    {
        $user = Auth::user();
        $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

        if (!$mahasiswa || !$mahasiswa->user->photo) {
            return response()->json(['message' => 'Tidak ada foto profil yang dapat dihapus.'], 404);
        }

        // Hapus foto dari storage
        Storage::delete($mahasiswa->user->photo);

        // Hapus path foto dari database
        $mahasiswa->user->photo = null;
        $mahasiswa->user->save();

        return response()->json(['message' => 'Foto profil berhasil dihapus.']);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

        if (!$mahasiswa) {
            return response()->json(['message' => 'Data mahasiswa tidak ditemukan.'], 404);
        }

        // Validasi input yang diterima
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'nim' => 'required|string|max:21',
            'jurusan' => 'required|string|max:255',
            'prodi' => 'required|string|max:255',
            'email' => 'required|email',
            'telepon' => 'nullable|string|max:15', // Pastikan Anda memvalidasi telepon
        ]);

        // Update data mahasiswa dengan data yang valid
        $mahasiswa->user->name = $validated['nama'];
        $mahasiswa->nim = $validated['nim'];
        $mahasiswa->classRoom->prodi->major->major_name = $validated['jurusan'];
        $mahasiswa->classRoom->prodi->prodi_name = $validated['prodi'];
        $mahasiswa->user->email = $validated['email'];
        $mahasiswa->phone = $validated['telepon'];
        $mahasiswa->user->save();
        $mahasiswa->save();

        return response()->json(['message' => 'Profile updated successfully.']);
    }
}
