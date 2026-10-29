<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisplayMaterial extends Model
{
    use HasFactory;

    protected $table = 'display_materials';

    protected $fillable = [
        'material_id',
        'custom_label',
        'nomor_urut',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'nomor_urut' => 'integer',
    ];

    /**
     * Relasi ke Master Material
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    /**
     * Accessor untuk Mendapatkan Nama Tampilan (Custom Label jika ada, atau Nama Asli Material)
     */
    public function getDisplayNameAttribute(): string
    {
        return !empty($this->custom_label) ? $this->custom_label : ($this->material->name ?? '-');
    }
}