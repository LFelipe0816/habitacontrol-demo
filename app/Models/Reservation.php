<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['common_area_id', 'unit_id', 'user_id', 'date', 'starts_at', 'ends_at', 'status', 'notes'])]
class Reservation extends Model
{
    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(CommonArea::class, 'common_area_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
