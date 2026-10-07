<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['unit_id', 'name', 'document', 'host', 'plate', 'reason', 'status', 'entered_at', 'left_at'])]
class Visitor extends Model
{
    protected function casts(): array
    {
        return ['entered_at' => 'datetime', 'left_at' => 'datetime'];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
