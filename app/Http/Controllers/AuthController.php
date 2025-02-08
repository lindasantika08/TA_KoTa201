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

    public function showResetForm(Request $request, $token)
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => $request->input('email', '')
        ]);
    }

    public function forgotPassword(Request $request)
{
    try {
        // Tambahkan log tambahan
        Log::info('SMTP Details', [
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'username' => config('mail.mailers.smtp.username'),
            'encryption' => config('mail.mailers.smtp.encryption')
        ]);

        $request->validate(['email' => 'required|email']);

        Log::info('Forgot Password Request', [
            'email' => $request->email
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        Log::info('Password Reset Link Status', [
            'status' => $status
        ]);

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Link reset password berhasil dikirim'])
            : response()->json(['message' => 'Gagal mengirim link reset'], 422);
    } catch (\Exception $e) {
        Log::error('Forgot Password Error', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json(['message' => 'Terjadi kesalahan'], 500);
    }
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
        function ($user) use ($request) {
            $user->forceFill([
                'password' => Hash::make($request->password),
                'remember_token' => Str::random(60)
            ])->save();

            event(new PasswordReset($user));
        }
    );

    if ($status === Password::PASSWORD_RESET) {
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully'
            ]);
        }
        
        return redirect()
            ->route('login')
            ->with('success', 'Your password has been successfully reset! You can now log in with your new password.');
    }

    return response()->json([
        'success' => false,
        'message' => 'Unable to reset password'
    ], 422);
}
}
