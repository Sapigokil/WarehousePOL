<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InStock extends Model
{
    protected $fillable = [
        'in_log_id', 'material_id', 'qty_received', 
        'serial_prefix', 'serial_start', 'serial_end'
    ];

    public function log(): BelongsTo
    {
        return $this->belongsTo(InLog::class, 'in_log_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}