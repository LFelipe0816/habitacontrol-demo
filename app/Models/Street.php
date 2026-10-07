<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['community_id', 'block_id', 'code', 'name', 'reference', 'lighting_points'])]
class Street extends Model
{
    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }
}
