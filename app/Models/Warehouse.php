<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $table = 'warehouses';

    protected $fillable = [
        'nomor_urut',
        'name',
        'code',
        'lokasi',
        'keterangan',
    ];
}