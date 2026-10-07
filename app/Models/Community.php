<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'plan_id', 'name', 'meta', 'units_count', 'levels', 'address', 'country', 'maintenance_fee', 'implementation_cost', 'status'])]
class Community extends Model
{
    protected function casts(): array
    {
        return ['maintenance_fee' => 'float', 'implementation_cost' => 'float'];
    }

    /** Residenciales que el personal puede administrar: los de su administradora (el superadmin ve todos). */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole('superadmin')) {
            return $query;
        }
        $companyId = $user->community?->company_id;

        return $companyId ? $query->where('company_id', $companyId) : $query->whereKey($user->community_id);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(Block::class)->orderBy('position');
    }

    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class)->orderBy('position');
    }

    public function streets(): HasMany
    {
        return $this->hasMany(Street::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function occupiedUnits(): HasMany
    {
        return $this->units()->where('occupancy', '!=', 'vacante');
    }

    public function unitsWithBalance(): HasMany
    {
        return $this->units()->where('balance', '>', 0);
    }

    public function openIncidents(): HasMany
    {
        return $this->incidents()->whereNotIn('status', ['Resuelta', 'Cerrada']);
    }

    public function serviceAssets(): HasMany
    {
        return $this->hasMany(ServiceAsset::class);
    }

    public function commonAreas(): HasMany
    {
        return $this->hasMany(CommonArea::class);
    }
}
