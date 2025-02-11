<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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

    public function validateToken(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Token invalid'
                ], 401);
            }

            return response()->json([
                'valid' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Token validation error'
            ], 500);
        }
    }

    public function logout(Request $request)
    {

        $user = $request->user();
        $user->tokens()->delete();
        return response()->json(['message' => 'Logout Berhasil']);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'oldPassword' => 'required',
            'newPassword' => 'required|min:8',
            'confirmPassword' => 'required|same:newPassword'
        ]);

        $user = Auth::user();

        if (!Hash::check($request->oldPassword, $user->password)) {
            return response()->json([
                'message' => 'Password lama tidak sesuai'
            ], 401);
        }

        try {
            DB::beginTransaction();

            // Hapus semua token yang ada
            $user->tokens()->delete();

            // Update password
            User::where('id', $user->id)->update([
                'password' => Hash::make($request->newPassword)
            ]);

            // Buat token baru
            $newToken = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return response()->json([
                'message' => 'Password berhasil diubah',
                'token' => $newToken,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error changing password: ' . $e->getMessage());

            return response()->json([
                'message' => 'Gagal mengubah password'
            ], 500);
        }
    }

    public function showResetForm($token)
{
    return Inertia::render('Auth/ResetPassword', [
        'token' => $token
    ]);
}

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => __($status)], 200)
            : response()->json(['message' => __($status)], 400);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => __($status)], 200)
            : response()->json(['message' => __($status)], 400);
    }
}
