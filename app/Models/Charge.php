<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['unit_id', 'concept', 'amount', 'due_date', 'status'])]
class Charge extends Model
{
    const STATUSES = ['Al día', 'Por vencer', 'Vencido', 'Moroso', 'Legal'];

    /** Días de atraso a partir de los cuales un cobro vencido pasa a "Moroso". */
    const MOROSO_AFTER_DAYS = 30;

    /** Cobros que el usuario puede ver: administración y junta los de sus residenciales; residentes, los de sus unidades. */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): Builder
    {
        return $user->hasRole('admin', 'junta') || $user->isResident()
            ? $query->whereIn('unit_id', Unit::visibleTo($user)->select('id'))
            : $query->whereRaw('1 = 0');
    }

    protected function casts(): array
    {
        return ['amount' => 'float', 'due_date' => 'date'];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
