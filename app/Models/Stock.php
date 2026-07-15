<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    protected $table = 'stocks';

    protected $fillable = [
        'no_surat_masuk',
        'tgl_masuk',
        'material_id',
        'warehouse_id',
        'seri_awal',
        'seri_akhir',
        'qty',
        'harga_satuan',
        'total_harga',
        'status',
        'keterangan',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
}