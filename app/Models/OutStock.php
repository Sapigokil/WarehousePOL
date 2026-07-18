<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'out_log_id',
        'stock_id',
        'qty_keluar',
        'prefix',
        'seri_awal',
        'seri_akhir',
    ];

    public function outLog(): BelongsTo
    {
        return $this->belongsTo(OutLog::class, 'out_log_id');
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }
}