<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['kind', 'text', 'user_id'])]
class IncidentEntry extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
