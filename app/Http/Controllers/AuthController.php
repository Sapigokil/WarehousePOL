<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Setting;
use App\Models\SystemLog;

class AuthController extends Controller
{
    public function index()
    {
        return view('auth.login');
    }

    public function authenticate(Request $request)
    {
        $request->validate([
            'username' => ['required'], 
            'password' => ['required'],
        ]);

        $loginInput = $request->input('username');
        $fieldType = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $credentials = [
            $fieldType => $loginInput,
            'password' => $request->input('password')
        ];

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            $user = Auth::user();
            
            // Ambil pengaturan dari database
            $allowDoubleLoginSetting = Setting::where('key', 'allow_double_login')->first();
            $allowDoubleLogin = $allowDoubleLoginSetting ? $allowDoubleLoginSetting->value : 0;
            
            // Ambil batas waktu kedaluwarsa sesi dari pengaturan sistem (default 120 menit)
            $sessionLifetime = config('session.lifetime');
            
            // Verifikasi apakah user benar-benar aktif atau ini hanyalah "Ghost Session"
            $isReallyActive = $user->last_seen_at && $user->last_seen_at->diffInMinutes(now()) < $sessionLifetime;
            
            // Deteksi gabungan: Harus online, beda sesi, DAN belum kedaluwarsa waktunya
            $isAlreadyActive = $user->is_online && $user->last_session_id !== session()->getId() && $isReallyActive;

            if ($allowDoubleLogin == 0 && $isAlreadyActive && !$user->is_display_screen) {
                session()->flash('warning_session', 'Peringatan: Akun ini terdeteksi sedang aktif di perangkat lain. Sistem telah memaksa keluar perangkat tersebut secara otomatis.');
            }
            
            $user->update([
                'is_online' => true,
                'last_seen_at' => now(),
                'last_session_id' => session()->getId(),
            ]);

            SystemLog::create([
                'user_id' => $user->id,
                'username' => $user->username,
                'action' => 'LOGIN',
                'table_name' => 'users',
                'record_id' => $user->id,
                'new_values' => [
                    'status' => 'User logged in',
                    'session_id' => session()->getId(),
                    'last_seen_at' => now()->toDateTimeString()
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return redirect()->intended('dashboard');
        }

        return back()->withErrors([
            'username' => 'Username/Email atau Password salah.',
        ])->onlyInput('username');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            
            SystemLog::create([
                'user_id' => $user->id,
                'username' => $user->username,
                'action' => 'LOGOUT',
                'table_name' => 'users',
                'record_id' => $user->id,
                'new_values' => [
                    'status' => 'User logged out voluntarily',
                    'last_seen_at' => now()->toDateTimeString()
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $user->update(['is_online' => false]);
        }

        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}