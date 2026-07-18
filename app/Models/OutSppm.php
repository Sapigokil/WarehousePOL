<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutSppm extends Model
{
    use HasFactory;

    protected $fillable = [
        'sppm_no',
        'sppm_date',
        'destination_id',
        'status',
        'keterangan',
        'created_by',
        'updated_by',
    ];

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(OutDetail::class, 'out_sppm_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(OutLog::class, 'out_sppm_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}