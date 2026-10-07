<?php

namespace App\Http\Resources;

use App\Models\ServiceAsset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Forma del tipo `ServiceAsset`. Requiere `maintenances` cargado para calcular último y próximo servicio.
class ServiceAssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $next = $this->maintenances->whereNull('performed_on')->sortBy('scheduled_for')->first();
        $last = $this->maintenances->whereNotNull('performed_on')->sortByDesc('performed_on')->first();

        return [
            'id' => $this->id,
            'category' => $this->category,
            'name' => $this->name,
            'location' => $this->location,
            'status' => $this->status,
            'status_label' => ServiceAsset::STATUSES[$this->status] ?? $this->status,
            'health' => $this->health,
            'availability' => $this->availability,
            'capacity' => $this->capacity,
            'reading_label' => $this->reading_label,
            'reading' => $this->reading,
            'responsible' => $this->responsible,
            'provider' => $this->provider,
            'routine' => $this->routine ?? [],
            'risk' => $this->risk,
            'last_maintenance' => $last?->performed_on->toDateString(),
            'next_maintenance' => $next?->scheduled_for->toDateString(),
            'next_maintenance_id' => $next?->id,
            'overdue' => (bool) $next?->scheduled_for->isPast() && ! $next->scheduled_for->isToday(),
        ];
    }
}
