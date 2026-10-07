<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['community_id', 'category', 'name', 'location', 'status', 'health', 'availability', 'capacity', 'reading_label', 'reading', 'responsible', 'provider', 'routine', 'risk'])]
class ServiceAsset extends Model
{
    const STATUSES = ['operativo' => 'Operativo', 'mantenimiento' => 'Mantenimiento', 'revision' => 'En revisión', 'alerta' => 'Atención requerida', 'fuera_servicio' => 'Fuera de servicio'];

    /** Estados que requieren seguimiento del equipo. */
    const NEEDS_ATTENTION = ['alerta', 'mantenimiento', 'revision', 'fuera_servicio'];

    /** Categorías que forman el mapa de agua del residencial. */
    const WATER = ['Agua', 'Calidad del agua', 'Almacenamiento'];

    protected function casts(): array
    {
        return ['routine' => 'array', 'availability' => 'float'];
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(ServiceMaintenance::class)->orderByDesc('scheduled_for');
    }
}
