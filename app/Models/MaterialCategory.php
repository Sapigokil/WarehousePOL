<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialCategory extends Model
{
    protected $table = 'material_categories';

    protected $fillable = [
        'name',
        'nomor_urut',
        'keterangan',
    ];
}