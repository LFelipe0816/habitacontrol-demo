<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'community_id', 'unit_id', 'document_id', 'phone', 'active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** Refleja el valor por defecto de la columna para usuarios aún sin recargar de la base. */
    protected $attributes = ['active' => true];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /** Todos los apartamentos vinculados (además de la unidad principal `unit_id`). */
    /** ¿Está vinculado a este apartamento (unidad principal o vínculo en `unit_user`)? */
    public function livesIn(Unit $unit): bool
    {
        return $this->unit_id === $unit->id || $this->units()->whereKey($unit->id)->exists();
    }

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class)->withPivot('relation')->withTimestamps();
    }

    public function reportedIncidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'reporter_id');
    }

    /** El superadministrador supera cualquier chequeo de rol. */
    public function hasRole(string ...$roles): bool
    {
        return $this->role === Role::Superadmin->value || in_array($this->role, $roles, true);
    }

    /** Personal de la administración o de operación (todo el que no es propietario/residente). */
    public function isStaff(): bool
    {
        return ! $this->isResident();
    }

    /** Roles que este usuario puede otorgar: el de superadministrador solo lo concede otro superadministrador. */
    public function assignableRoles(): array
    {
        return $this->hasRole('superadmin') ? Role::values() : array_values(array_diff(Role::values(), [Role::Superadmin->value]));
    }

    /** Propietarios y residentes solo ven lo que les pertenece. */
    public function isResident(): bool
    {
        return in_array($this->role, [Role::Propietario->value, Role::Residente->value], true);
    }
}
