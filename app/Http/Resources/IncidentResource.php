<?php

namespace App\Http\Resources;

use App\Models\Incident;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

// Forma que espera el tipo `Incident` en resources/js/types/index.ts
class IncidentResource extends JsonResource
{
    public static $wrap = null; // Inertia recibe el objeto directo, sin { data }

    public function toArray(Request $request): array
    {
        $files = fn ($col) => $this->whenLoaded('attachments', fn () => $this->attachments->where('collection', $col)->values()
            ->map(fn ($a) => ['id' => $a->id, 'url' => Storage::url($a->path), 'label' => $a->name, 'mime' => $a->mime]), []);

        return [
            'id' => $this->code,
            'title' => $this->title,
            'type' => $this->type,
            'location' => $this->location,
            'location_type' => Incident::LOCATION_TYPES[$this->location_type] ?? null,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status,
            'assignee' => $this->assignee?->name,
            'reporter' => $this->reporter?->name,
            'unit' => $this->unit?->code,
            'recurring' => (bool) $this->recurring,
            'created_at' => $this->created_at->toIso8601String(),
            'evidence' => $files('evidence'),
            'solution' => $files('solution'),
            'timeline' => $this->whenLoaded('entries', fn () => $this->entries->map(fn ($e) => [
                'who' => $e->user->name, 'when' => $e->created_at->diffForHumans(), 'kind' => $e->kind, 'text' => $e->text,
            ])),
        ];
    }
}
