<?php

namespace App\Services;

use App\Models\InStock;
use App\Models\OutStock;
use App\Models\Material;
use Illuminate\Support\Facades\DB;

class StockLedgerService
{
    /**
     * Menghitung sisa rentang seri yang tersedia untuk material dan prefix tertentu.
     */
    public function getAvailableSerialRanges($materialId, $prefix = null)
    {
        // 1. Ambil seluruh data Inbound (Barang Masuk)
        $inbounds = InStock::where('material_id', $materialId)
            ->where('serial_prefix', $prefix)
            ->where('qty_received', '>', 0)
            ->orderBy('serial_start', 'asc')
            ->get(['serial_start', 'serial_end'])
            ->map(function ($item) {
                return [
                    'start' => (int) $item->serial_start,
                    'end'   => (int) $item->serial_end,
                ];
            })->toArray();

        // 2. Ambil seluruh data Outbound (Barang Keluar)
        $outbounds = OutStock::where('material_id', $materialId)
            ->where('prefix', $prefix)
            ->where('qty_keluar', '>', 0)
            ->orderBy('seri_awal', 'asc')
            ->get(['seri_awal', 'seri_akhir'])
            ->map(function ($item) {
                return [
                    'start' => (int) $item->seri_awal,
                    'end'   => (int) $item->seri_akhir,
                ];
            })->toArray();

        // 3. Algoritma Pengurangan Himpunan (Set Difference)
        // Gabungkan rentang masuk yang berurutan agar rapi
        $available = $this->mergeRanges($inbounds);

        foreach ($outbounds as $out) {
            $newAvailable = [];
            foreach ($available as $av) {
                // A. Tidak beririsan sama sekali
                if ($out['end'] < $av['start'] || $out['start'] > $av['end']) {
                    $newAvailable[] = $av;
                } 
                // B. Outbound menutupi seluruh rentang available (Lunas Habis)
                else if ($out['start'] <= $av['start'] && $out['end'] >= $av['end']) {
                    // Abaikan rentang ini
                } 
                // C. Outbound membelah rentang available di tengah-tengah
                else if ($out['start'] > $av['start'] && $out['end'] < $av['end']) {
                    $newAvailable[] = ['start' => $av['start'], 'end' => $out['start'] - 1];
                    $newAvailable[] = ['start' => $out['end'] + 1, 'end' => $av['end']];
                } 
                // D. Outbound memotong ujung kiri available
                else if ($out['start'] <= $av['start'] && $out['end'] >= $av['start'] && $out['end'] < $av['end']) {
                    $newAvailable[] = ['start' => $out['end'] + 1, 'end' => $av['end']];
                } 
                // E. Outbound memotong ujung kanan available
                else if ($out['start'] > $av['start'] && $out['start'] <= $av['end'] && $out['end'] >= $av['end']) {
                    $newAvailable[] = ['start' => $av['start'], 'end' => $out['start'] - 1];
                }
            }
            $available = $newAvailable;
        }

        return $available;
    }

    /**
     * Menghitung total stok kuantitas global (termasuk non-seri)
     */
    public function getGlobalStock($materialId)
    {
        $totalIn = InStock::where('material_id', $materialId)->sum('qty_received');
        $totalOut = OutStock::where('material_id', $materialId)->sum('qty_keluar');

        return $totalIn - $totalOut;
    }

    /**
     * Helper internal untuk menggabungkan rentang masuk yang berdekatan atau overlap
     */
    private function mergeRanges($ranges)
    {
        if (empty($ranges)) return [];

        usort($ranges, function ($a, $b) {
            return $a['start'] <=> $b['start'];
        });

        $merged = [];
        $current = $ranges[0];

        for ($i = 1; $i < count($ranges); $i++) {
            if ($ranges[$i]['start'] <= $current['end'] + 1) {
                $current['end'] = max($current['end'], $ranges[$i]['end']);
            } else {
                $merged[] = $current;
                $current = $ranges[$i];
            }
        }
        $merged[] = $current;

        return $merged;
    }
}