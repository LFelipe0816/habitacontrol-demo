<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['service_asset_id', 'performed_by', 'scheduled_for', 'performed_on', 'type', 'notes'])]
class ServiceMaintenance extends Model
{
    protected function casts(): array
    {
        return ['scheduled_for' => 'date', 'performed_on' => 'date'];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(ServiceAsset::class, 'service_asset_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
