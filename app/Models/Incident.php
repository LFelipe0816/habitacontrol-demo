<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['community_id', 'title', 'type', 'location', 'location_type', 'block_id', 'building_id', 'street_id', 'description', 'scope', 'priority', 'status', 'assignee_id', 'assignee_role', 'unit_id', 'recurring', 'first_response_at', 'closed_at', 'resident_confirmed_at', 'resident_feedback', 'admin_notes', 'resolution', 'recurrence_note'])]
class Incident extends Model
{
    const STATES = ['Recibida', 'En revisión', 'Asignada', 'En gestión', 'Resuelta', 'Cerrada'];

    const CLOSED_STATES = ['Resuelta', 'Cerrada'];

    const PRIORITIES = ['Alta', 'Media', 'Baja'];

    const TYPES = ['Plomería', 'Eléctrico', 'Ascensor', 'Seguridad', 'Estructura', 'Limpieza', 'Mantenimiento', 'Ruido', 'Área común en mal estado', 'Parqueos', 'Violación de normas', 'Otro'];

    const SCOPES = ['General', 'Convivencia', 'Área común', 'Iluminación exterior', 'Circulación vehicular', 'Mantenimiento edificio', 'Agua y drenaje', 'Seguridad y accesos'];

    const LOCATION_TYPES = ['apartment' => 'Apartamento', 'building' => 'Edificio', 'block' => 'Manzana', 'street' => 'Calle', 'common_area' => 'Área común'];

    protected function casts(): array
    {
        return ['recurring' => 'boolean', 'first_response_at' => 'datetime', 'closed_at' => 'datetime', 'resident_confirmed_at' => 'datetime'];
    }

    // El código público (INC-0001) deriva del id, por eso se asigna tras insertar.
    protected static function booted(): void
    {
        static::created(function (self $incident) {
            $incident->forceFill(['code' => 'INC-'.str_pad($incident->id, 4, '0', STR_PAD_LEFT)])->saveQuietly();
        });
    }

    /**
     * Casos que el usuario puede ver. Propietarios y residentes solo los que reportaron (privacidad por caso);
     * seguridad y mantenimiento los que reportaron o tienen asignados; el resto, los de sus residenciales.
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole('superadmin')) {
            return $query;
        }
        if ($user->isResident()) {
            return $query->where('reporter_id', $user->id);
        }
        $inScope = $query->whereIn('community_id', Community::visibleTo($user)->select('id'));

        return $user->hasRole('admin', 'junta')
            ? $inScope
            : $inScope->where(fn ($q) => $q->where('assignee_id', $user->id)->orWhere('assignee_role', $user->role)->orWhere('reporter_id', $user->id));
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

    public function street(): BelongsTo
    {
        return $this->belongsTo(Street::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(IncidentEntry::class)->oldest();
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(IncidentAttachment::class);
    }
}
