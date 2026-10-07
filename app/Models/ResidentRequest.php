<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// El nombre `Request` chocaría con Illuminate\Http\Request; la tabla sí se llama `requests`.
#[Fillable(['unit_id', 'requester_id', 'assignee_id', 'title', 'description', 'status', 'response'])]
class ResidentRequest extends Model
{
    protected $table = 'requests';

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }
}
