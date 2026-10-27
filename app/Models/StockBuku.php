<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockBuku extends Model
{
    use HasFactory;

    protected $table = 'stock_buku';

    protected $fillable = [
        'material_id',
        'in_sppm_id',
        'in_stock_id',
        'out_sppm_id',
        'out_stock_id',
        'tipe_baris',
        'prefix',
        'seri_awal',
        'seri_akhir',
        'qty',
        'keterangan',
    ];

    /**
     * Relasi ke Master Barang / Materiil
     */
    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    /**
     * Relasi ke Dokumen SPPM Masuk
     */
    public function inSppm()
    {
        return $this->belongsTo(InSppm::class, 'in_sppm_id');
    }

    /**
     * Relasi ke Log Fisik Masuk (Untuk melacak baris fisik asalnya)
     */
    public function inStock()
    {
        return $this->belongsTo(InStock::class, 'in_stock_id');
    }

    /**
     * Relasi ke Dokumen SPPM Keluar
     */
    public function outSppm()
    {
        return $this->belongsTo(OutSppm::class, 'out_sppm_id');
    }

    /**
     * Relasi ke Log Fisik Keluar
     */
    public function outStock()
    {
        return $this->belongsTo(OutStock::class, 'out_stock_id');
    }
}