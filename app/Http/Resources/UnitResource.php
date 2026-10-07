<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Forma del tipo `UnitSummary`: lo mínimo para dibujar el apartamento en la cuadrícula del edificio.
class UnitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'number' => $this->apartment_number ?? $this->code,
            'occupancy' => $this->occupancy,
            'balance' => $this->balance,
        ];
    }
}
