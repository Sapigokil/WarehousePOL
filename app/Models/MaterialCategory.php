<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialCategory extends Model
{
    protected $table = 'material_categories';

    protected $fillable = [
        'name',
        'nomor_urut',
        'keterangan',
    ];

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class, 'material_category_id');
    }
}