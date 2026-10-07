<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['community_id', 'author_id', 'category', 'title', 'body', 'audience'])]
class Notice extends Model
{
    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
