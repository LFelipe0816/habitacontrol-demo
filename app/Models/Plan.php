<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['key', 'name', 'price_per_unit', 'implementation_cost'])]
class Plan extends Model
{
    protected function casts(): array
    {
        return ['price_per_unit' => 'float', 'implementation_cost' => 'float'];
    }

    public function communities(): HasMany
    {
        return $this->hasMany(Community::class);
    }

    /** Cotización mensual y de implementación para un número de unidades. */
    public function quote(int $units): array
    {
        return [
            'monthly' => round($this->price_per_unit * $units, 2),
            'implementation' => $this->implementation_cost,
        ];
    }
}
