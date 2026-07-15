<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InSppm extends Model
{
    protected $fillable = [
        'sppm_no',
        'sppm_date',
        'material_category_id',
        'status',
        'notes',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(MaterialCategory::class, 'material_category_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(InDetail::class, 'in_sppm_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(InLog::class, 'in_sppm_id');
    }
}