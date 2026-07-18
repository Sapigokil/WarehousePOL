<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'out_sppm_id',
        'material_id',
        'target_qty',
        'harga_satuan',
        'harga_total',
    ];

    public function outSppm(): BelongsTo
    {
        return $this->belongsTo(OutSppm::class, 'out_sppm_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}