<?php

namespace App\Services;

use App\Models\ServiceAsset;
use App\Models\ServiceMaintenance;
use App\Models\User;

/** Cambios sobre la infraestructura compartida; cada uno queda en la bitácora técnica (activity_logs). */
class ServiceAssetWorkflow
{
    public function create(User $actor, int $communityId, array $data): ServiceAsset
    {
        $asset = ServiceAsset::create($data + ['community_id' => $communityId]);
        $this->log($asset, $actor, 'Activo registrado', "{$asset->name} se agregó a servicios generales.");

        return $asset;
    }

    public function update(ServiceAsset $asset, User $actor, array $data): void
    {
        $asset->fill($data);
        $changed = array_keys($asset->getDirty());
        $statusFrom = $asset->getOriginal('status');
        $asset->save();

        if ($changed) {
            $detail = in_array('status', $changed, true)
                ? 'Estado: '.ServiceAsset::STATUSES[$statusFrom].' → '.ServiceAsset::STATUSES[$asset->status].'.'
                : 'Se actualizó: '.implode(', ', $changed).'.';
            $this->log($asset, $actor, 'Activo actualizado', "{$asset->name}. {$detail}");
        }
    }

    public function schedule(ServiceAsset $asset, User $actor, array $data): ServiceMaintenance
    {
        $maintenance = $asset->maintenances()->create($data);
        $this->log($asset, $actor, 'Mantenimiento programado', "{$asset->name}: {$data['type']} para el {$maintenance->scheduled_for->format('d/m/Y')}.");

        return $maintenance;
    }

    /** Cierra el servicio; si el equipo estaba "en mantenimiento" vuelve a operar. */
    public function complete(ServiceMaintenance $maintenance, User $actor, array $data): void
    {
        $asset = $maintenance->asset;
        $maintenance->update(['performed_on' => $data['performed_on'], 'performed_by' => $actor->id, 'notes' => $data['notes'] ?? $maintenance->notes]);

        $asset->fill(array_filter(['health' => $data['health'] ?? null], fn ($v) => $v !== null));
        if ($asset->status === 'mantenimiento') {
            $asset->status = 'operativo';
        }
        $asset->save();

        $this->log($asset, $actor, 'Mantenimiento completado', "{$asset->name}: servicio {$maintenance->type} realizado el {$maintenance->performed_on->format('d/m/Y')}.");
    }

    private function log(ServiceAsset $asset, User $actor, string $action, string $detail): void
    {
        $asset->activities()->create(['community_id' => $asset->community_id, 'user_id' => $actor->id, 'action' => $action, 'detail' => $detail]);
    }
}
