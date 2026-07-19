<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Setting;
use Symfony\Component\HttpFoundation\Response;

class CheckIdleTimeout
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            // Ambil batas waktu dari database (default 120 menit jika kosong)
            $timeoutSetting = Setting::where('key', 'login_timeout')->value('value');
            $timeoutLimit = $timeoutSetting ? (int) $timeoutSetting : 120;
            
            // Konversi menit ke detik
            $timeoutSeconds = $timeoutLimit * 60;
            
            $lastActivity = session('last_activity');

            // Jika ada riwayat aktivitas sebelumnya, periksa selisih waktunya
            if ($lastActivity && (time() - $lastActivity > $timeoutSeconds)) {
                
                // Logout user
                Auth::logout();
                
                // Hapus data sesi lama
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                // Redirect ke halaman login dengan pesan alert
                return redirect()->route('login')->withErrors(['Sesi Anda telah berakhir karena tidak ada aktivitas (Idle) selama lebih dari ' . $timeoutLimit . ' menit. Silakan login kembali.']);
            }

            // Perbarui waktu aktivitas terakhir menjadi waktu sekarang (aktif)
            session(['last_activity' => time()]);
        }

        return $next($request);
    }
}