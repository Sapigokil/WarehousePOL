<?php

namespace App\Http\Controllers;

use App\Models\SystemLog;
use Illuminate\Http\Request;

class SystemLogController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $limit = $request->input('limit', 25);
        $action = $request->input('action');
        $perPage = $limit === 'all' ? 999999 : $limit;

        $logs = SystemLog::when($search, function ($query, $search) {
                return $query->where(function($q) use ($search) {
                    $q->where('username', 'like', '%' . $search . '%')
                      ->orWhere('table_name', 'like', '%' . $search . '%')
                      ->orWhere('ip_address', 'like', '%' . $search . '%');
                });
            })
            ->when($action, function ($query, $action) {
                return $query->where('action', $action);
            })
            ->orderBy('created_at', 'DESC')
            ->paginate($perPage)
            ->withQueryString();

        // Mengambil daftar aksi unik untuk filter dropdown
        $availableActions = SystemLog::select('action')->distinct()->pluck('action');

        return view('sislogs.index', compact('logs', 'search', 'limit', 'action', 'availableActions'));
    }
}