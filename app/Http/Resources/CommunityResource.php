<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Forma del tipo `Community` (resources/js/types). Los contadores vienen de `withCount`/`withSum` en el controlador.
class CommunityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'status' => $this->status,
            'plan' => $this->plan?->name,
            'units_count' => $this->units_count,
            'loaded_units' => $this->loaded_units_count ?? null,
            'occupied_units' => $this->whenCounted('occupiedUnits'),
            'units_with_balance' => $this->whenCounted('unitsWithBalance'),
            'open_incidents' => $this->whenCounted('openIncidents'),
            'balance' => (float) ($this->units_sum_balance ?? 0),
            'maintenance_fee' => $this->maintenance_fee,
        ];
    }
}
