<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VisitorResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'document' => $this->document,
            'unit' => $this->unit->code,
            'host' => $this->host,
            'plate' => $this->plate,
            'entered_at' => $this->entered_at->toIso8601String(),
            'left_at' => $this->left_at?->toIso8601String(),
        ];
    }
}
