<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    protected $table = 'materials';

    protected $fillable = [
        'parent_id',
        'nomor_urut',
        'code',
        'name',
        'material_category_id',
        'satuan',
        'minimal_stok',
        'pakai_seri',
        'ismain',
        'is_harga',
        'jmlxinduk',
        'keterangan',
        'tnkb_rpt',
        'tnkb_r',
        'tnkb_ev',
        'sbst_judul',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(MaterialCategory::class, 'material_category_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Material::class, 'parent_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class, 'material_id');
    }
}