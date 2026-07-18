<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'out_sppm_id',
        'batch_number',
        'tgl_keluar',
        'keterangan',
    ];

    public function outSppm(): BelongsTo
    {
        return $this->belongsTo(OutSppm::class, 'out_sppm_id');
    }

    public function outStocks(): HasMany
    {
        return $this->hasMany(OutStock::class, 'out_log_id');
    }
}