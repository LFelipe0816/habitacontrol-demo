<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['community_id', 'block_id', 'code', 'name', 'floors', 'apartments_per_floor', 'position'])]
class Building extends Model
{
    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }
}
