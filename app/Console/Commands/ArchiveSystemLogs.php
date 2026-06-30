<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SystemLog;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ArchiveSystemLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:archive';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Arsipkan system log yang berumur lebih dari 6 bulan ke file JSON fisik lalu hapus dari database.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Tetapkan batas waktu: 6 bulan yang lalu dari hari eksekusi
        $thresholdDate = Carbon::now()->subMonths(6);

        // Ambil data yang melewati batas waktu
        // Menggunakan get() untuk menarik data sebagai koleksi Model
        $logs = SystemLog::where('created_at', '<', $thresholdDate)->get();

        if ($logs->isEmpty()) {
            $this->info('Tidak ada data log berusia lebih dari 6 bulan untuk diarsipkan saat ini.');
            return;
        }

        // Tentukan penamaan file: system_logs_archive_YYYY_MM_DD.json
        // Format tanggal diambil saat file ini dicetak
        $fileName = 'archives/system_logs_archive_' . Carbon::now()->format('Y_m_d') . '.json';

        // Simpan ke sistem file penyimpanan internal Laravel (storage/app/archives)
        Storage::disk('local')->put($fileName, $logs->toJson(JSON_PRETTY_PRINT));

        // Setelah file fisik berhasil dibuat dan disimpan, hapus data asli dari database
        SystemLog::where('created_at', '<', $thresholdDate)->delete();

        $this->info('Sukses! Sebanyak ' . $logs->count() . ' baris log berhasil dipindahkan ke ' . $fileName);
    }
}