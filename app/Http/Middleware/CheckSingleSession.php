<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Setting;

class CheckSingleSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Ambil nilai dari database, default ke 0 jika pengaturan tidak sengaja terhapus
            $allowDoubleLoginSetting = Setting::where('key', 'allow_double_login')->first();
            $allowDoubleLogin = $allowDoubleLoginSetting ? $allowDoubleLoginSetting->value : 0;

            // Jika AllowDoubleLogin dinonaktifkan (0) dan bukan layar display
            // Cek apakah session ID di database berbeda dengan session ID di browser ini
            if ($allowDoubleLogin == 0 && !$user->is_display_screen && $user->last_session_id !== session()->getId()) {
                
                // Hancurkan sesi pengguna lama di belakang layar
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                // Kembalikan view khusus modal peringatan
                return response()->view('auth.kicked');
            }
        }

        return $next($request);
    }
}