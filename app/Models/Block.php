<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['community_id', 'code', 'name', 'position'])]
class Block extends Model
{
    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class)->orderBy('position');
    }

    public function streets(): HasMany
    {
        return $this->hasMany(Street::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }
}
