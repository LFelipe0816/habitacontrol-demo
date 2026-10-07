<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['community_id', 'uploaded_by', 'title', 'category', 'visibility', 'path', 'file_name'])]
class Document extends Model
{
    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
