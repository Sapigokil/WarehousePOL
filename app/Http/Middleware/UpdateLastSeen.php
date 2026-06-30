<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastSeen
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Hanya update ke database jika waktu terakhir dilihat sudah lebih dari 5 menit
            // Ini untuk mencegah database kelebihan beban jika user mengeklik menu terlalu cepat
            if (!$user->last_seen_at || $user->last_seen_at->diffInMinutes(now()) >= 5) {
                $user->update([
                    'last_seen_at' => now(),
                    'is_online' => true // Pastikan status online menyala
                ]);
            }
        }

        return $next($request);
    }
}