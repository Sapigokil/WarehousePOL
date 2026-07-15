<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InLog extends Model
{
    protected $fillable = [
        'in_sppm_id',
        'batch_number',
        'receive_date',
        'receiver_name',
        'notes',
    ];

    public function sppm(): BelongsTo
    {
        return $this->belongsTo(InSppm::class, 'in_sppm_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(InStock::class, 'in_log_id');
    }
}