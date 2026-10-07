<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'tax_id', 'phone'])]
class Company extends Model
{
    public function communities(): HasMany
    {
        return $this->hasMany(Community::class);
    }
}
