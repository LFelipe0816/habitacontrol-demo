<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['community_id', 'block_id', 'building_id', 'code', 'apartment_number', 'floor', 'parking', 'maintenance_fee', 'balance', 'occupancy', 'move_in_date', 'emergency_contact', 'vehicles', 'notes'])]
class Unit extends Model
{
    protected function casts(): array
    {
        return [
            'maintenance_fee' => 'float',
            'balance' => 'float',
            'move_in_date' => 'date',
            'emergency_contact' => 'array',
            'vehicles' => 'array',
        ];
    }

    /** Unidades que el usuario puede ver: las suyas si es propietario/residente; las de sus residenciales si es personal. */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): Builder
    {
        if ($user->isResident()) {
            return $query->whereIn('id', $user->units()->pluck('units.id')->push($user->unit_id)->filter());
        }

        return $query->whereIn('community_id', Community::visibleTo($user)->select('id'));
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /** Usuarios cuya unidad principal es esta (`users.unit_id`). */
    public function residents(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Todas las personas vinculadas, con su relación (propietario, residente, inquilino). */
    public function people(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('relation')->withTimestamps();
    }

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(Charge::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }
}
