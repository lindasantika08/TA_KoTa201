<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

use Inertia\Inertia;


class AuthController extends Controller
{

    public function index()
    {

        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role
                ],
                'message' => 'Login berhasil'
            ]);
        }

        return response()->json([
            'message' => 'Email atau password salah'
        ], 401);
    }

    public function logout(Request $request)
    {
        try {
            // Mendapatkan token yang sedang aktif
            $token = $request->user()->currentAccessToken();

            // Menghapus token yang aktif (baik itu PersonalAccessToken atau TransientToken)
            $token->delete();  // Menghapus token

            return response()->json(['message' => 'Logout Berhasil']);
        } catch (\Exception $e) {
            Log::error('Logout Error: ' . $e->getMessage());
            return response()->json(['message' => 'Logout gagal', 'error' => $e->getMessage()], 500);
        }
    }
}
