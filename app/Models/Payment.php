<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['unit_id', 'user_id', 'amount', 'method', 'reference'])]
class Payment extends Model
{
    /** Pagos visibles: los de las unidades que el usuario puede ver, y solo si su rol consulta finanzas. */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): Builder
    {
        return $user->hasRole('admin', 'junta') || $user->isResident()
            ? $query->whereIn('unit_id', Unit::visibleTo($user)->select('id'))
            : $query->whereRaw('1 = 0');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function charge(): BelongsTo
    {
        return $this->belongsTo(Charge::class);
    }

    protected function casts(): array
    {
        return ['amount' => 'float'];
    }
}
