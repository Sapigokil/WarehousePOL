<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Destination extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor_urut',
        'name',
        'nama',
        'pangkat_nrp',
        'jabatan',
        'keterangan',
    ];
}