<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request) {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message'=>'Login Gagal'], 401);
        }

        return response()->json(['message' => 'Login Berhasil']);
    }

    public function logout(Request $request){
        $request->users()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout Berhasil']);
    }
}
