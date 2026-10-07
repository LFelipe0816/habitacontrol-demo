<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['community_id', 'name', 'capacity', 'active'])]
class CommonArea extends Model
{
    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
