<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

// Vista completa del caso (tipo `IncidentDetail`). Los datos internos solo salen para el personal.
class IncidentDetailResource extends IncidentResource
{
    public function toArray(Request $request): array
    {
        $base = parent::toArray($request);
        $internal = Gate::allows('viewInternal', $this->resource);

        // Quien reportó no ve las notas internas de la bitácora.
        if (! $internal) {
            $base['timeline'] = $this->entries->where('kind', '!=', 'int')->values()->map(fn ($e) => [
                'who' => $e->user->name, 'when' => $e->created_at->diffForHumans(), 'kind' => $e->kind, 'text' => $e->text,
            ]);
        }

        return $base + [
            'community' => $this->community?->name,
            'scope' => $this->scope,
            'assignee_id' => $this->assignee_id,
            'assignee_role' => $this->assignee_role,
            'first_response_at' => $this->first_response_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            // Tiempos del SLA en minutos; nulos mientras el hito no ocurra.
            'response_minutes' => $this->first_response_at ? (int) $this->created_at->diffInMinutes($this->first_response_at) : null,
            'resolution_minutes' => $this->closed_at ? (int) $this->created_at->diffInMinutes($this->closed_at) : null,
            'resolution' => $this->resolution,
            'recurrence_note' => $this->recurrence_note,
            'resident_confirmed_at' => $this->resident_confirmed_at?->toIso8601String(),
            'resident_feedback' => $this->resident_feedback,
            'admin_notes' => $internal ? $this->admin_notes : null,
        ];
    }
}
