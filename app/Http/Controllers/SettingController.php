<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        $data = [
            'app_theme'          => $settings['app_theme'] ?? 'light-blue',
            'inbound_mode'       => $settings['inbound_mode'] ?? 'mode-1',
            'max_batch'          => $settings['max_batch'] ?? '5', // Default maksimal 5 batch
            'signatory_name'     => $settings['signatory_name'] ?? 'NAMA DIREKTUR',
            'signatory_nrp'      => $settings['signatory_nrp'] ?? 'NRP. 12345678',
            'signatory_position' => $settings['signatory_position'] ?? 'BRIGADIR JENDERAL POLISI',
            'allow_double_login' => $settings['allow_double_login'] ?? '0',
            'login_timeout'      => $settings['login_timeout'] ?? '120',
        ];

        return view('settings.index', compact('data'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'app_theme'          => 'required|in:navy-blue,modern-blue,ocean-blue,light-blue',
            'inbound_mode'       => 'required|in:mode-1,mode-2,mode-3',
            'max_batch'          => 'nullable|integer|min:2',
            'signatory_name'     => 'required|string|max:255',
            'signatory_nrp'      => 'nullable|string|max:255',
            'signatory_position' => 'required|string|max:255',
            'allow_double_login' => 'required|in:1,0',
            'login_timeout'      => 'required|integer|min:1|max:1440', 
        ]);

        $settings = $request->except(['_token', '_method']);
        
        foreach ($settings as $key => $value) {
            // Jika max_batch dikosongkan saat submit (karena hidden di mode-1), set default ke 5
            if ($key === 'max_batch' && is_null($value)) {
                $value = 5;
            }

            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return redirect()->route('settings.index')->with('success', 'Pengaturan global berhasil diperbarui.');
    }
}