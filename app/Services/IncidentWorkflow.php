<?php

namespace App\Services;

use App\Models\Block;
use App\Models\Building;
use App\Models\Incident;
use App\Models\Street;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * Reglas del ciclo de vida de una incidencia (Inciden 360): recibir, asignar, documentar y confirmar.
 * Cada cambio deja una entrada en la bitácora del caso y otra en la auditoría general.
 */
class IncidentWorkflow
{
    /** @param  array<string, mixed>  $data  campos ya validados por StoreIncidentRequest  @param  UploadedFile[]  $files */
    public function open(User $reporter, int $communityId, array $data, array $files = []): Incident
    {
        $incident = $reporter->reportedIncidents()->create([
            'community_id' => $communityId,
            'status' => 'Recibida',
            'location' => $this->describeLocation($data),
            'scope' => $data['scope'] ?? 'General',
            'unit_id' => $data['location_type'] === 'apartment' ? $data['unit_id'] : null,
            'building_id' => $data['location_type'] === 'building' ? $data['building_id'] : null,
            'block_id' => $data['location_type'] === 'block' ? $data['block_id'] : null,
            'street_id' => $data['location_type'] === 'street' ? $data['street_id'] : null,
        ] + collect($data)->only(['title', 'type', 'priority', 'description', 'location_type'])->all());

        $this->log($incident, $reporter, 'Recibida', "Reporte creado por {$reporter->name}.");
        $this->attach($incident, 'evidence', $files);

        return $incident;
    }

    public function changeStatus(Incident $incident, User $actor, string $status): void
    {
        $from = $incident->status;
        if ($from === $status) {
            return;
        }
        $closing = in_array($status, Incident::CLOSED_STATES, true);

        $incident->update([
            'status' => $status,
            // Primera respuesta: la primera vez que el caso sale de "Recibida" (base del SLA).
            'first_response_at' => $incident->first_response_at ?? ($status !== 'Recibida' ? now() : null),
            'closed_at' => $closing ? ($incident->closed_at ?? now()) : null,
            // Reabrir el caso invalida la confirmación anterior.
            ...($closing ? [] : ['resident_confirmed_at' => null]),
        ]);
        $this->log($incident, $actor, $status, "Estado: {$from} → {$status}.");
    }

    /** Asigna a una persona o, si aún no hay, a un rol; un caso recién recibido pasa a "Asignada". */
    public function assign(Incident $incident, User $actor, ?User $assignee, ?string $role): void
    {
        $incident->update(['assignee_id' => $assignee?->id, 'assignee_role' => $assignee?->role ?? $role]);
        $target = $assignee?->name ?? ($role ? "rol {$role}" : 'nadie');
        $this->log($incident, $actor, 'Asignada', "Responsable: {$target}.");

        if (in_array($incident->status, ['Recibida', 'En revisión'], true) && ($assignee || $role)) {
            $this->changeStatus($incident, $actor, 'Asignada');
        }
    }

    public function updateDetail(Incident $incident, User $actor, array $data): void
    {
        $incident->update($data);
        $this->log($incident, $actor, 'Caso actualizado', 'Se actualizaron los datos de gestión: '.implode(', ', array_keys($data)).'.', timeline: false);
    }

    /** @param  UploadedFile[]  $files */
    public function attach(Incident $incident, string $collection, array $files, ?User $actor = null): void
    {
        foreach ($files as $file) {
            $incident->attachments()->create([
                'collection' => $collection,
                'path' => $file->store('incidents', 'public'),
                'name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }
        if ($actor && $files) {
            $what = $collection === 'solution' ? 'de la solución' : 'del problema';
            $this->log($incident, $actor, 'Evidencia', count($files).' archivo(s) '.$what.' agregado(s).');
        }
    }

    /** Quien reportó valida la solución: la confirma (cierra el caso) o la rechaza (vuelve a gestión). */
    public function confirm(Incident $incident, User $actor, bool $resolved, ?string $feedback): void
    {
        $incident->update(['resident_feedback' => $feedback]);

        if ($resolved) {
            $incident->update(['resident_confirmed_at' => now()]);
            $this->changeStatus($incident, $actor, 'Cerrada');
            $this->log($incident, $actor, 'Confirmada', 'El residente confirmó que el caso fue atendido.', timeline: false);
        } else {
            $this->changeStatus($incident, $actor, 'En gestión');
            $incident->entries()->create(['user_id' => $actor->id, 'kind' => 'res', 'text' => 'El residente indicó que el problema persiste'.($feedback ? ": {$feedback}" : '.')]);
        }
    }

    private function describeLocation(array $d): string
    {
        $parts = match ($d['location_type']) {
            'apartment' => ($u = Unit::with('building', 'block')->find($d['unit_id'])) ? ["Apto {$u->apartment_number}", $u->building?->name, $u->block?->name] : [],
            'building' => ($b = Building::with('block')->find($d['building_id'])) ? [$b->name, $b->block?->name] : [],
            'block' => [Block::find($d['block_id'])?->name],
            'street' => [Street::find($d['street_id'])?->name],
            default => ['Área común'],
        };
        $where = implode(' · ', array_filter($parts ?: ['Área común']));

        return filled($d['reference'] ?? null) ? "{$where} — {$d['reference']}" : $where;
    }

    /** Registra la acción en la auditoría y, salvo que se pida lo contrario, en la línea de tiempo del caso. */
    private function log(Incident $incident, User $actor, string $action, string $detail, bool $timeline = true): void
    {
        if ($timeline) {
            $incident->entries()->create(['user_id' => $actor->id, 'kind' => 'acc', 'text' => $detail]);
        }
        $incident->activities()->create(['community_id' => $incident->community_id, 'user_id' => $actor->id, 'action' => $action, 'detail' => "{$incident->code}: {$detail}"]);
    }
}
