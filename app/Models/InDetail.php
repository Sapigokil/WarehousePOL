<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InDetail extends Model
{
    protected $fillable = [
        'in_sppm_id', 'material_id', 'target_qty', 'qty_huruf', 
        'harga_satuan', 'harga_total', 
        'sppm_serial_prefix', 'sppm_serial_start', 'sppm_serial_end'
    ];

    public function sppm(): BelongsTo
    {
        return $this->belongsTo(InSppm::class, 'in_sppm_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}