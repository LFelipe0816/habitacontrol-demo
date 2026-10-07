<?php

namespace App\Http\Requests;

use App\Models\Community;
use App\Models\Incident;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Residencial del reporte: el del residente, o el elegido por el personal entre los que administra. */
    public function communityId(): int
    {
        $user = $this->user();
        if ($user->isResident() || ! $this->filled('community_id')) {
            return (int) $user->community_id;
        }

        return (int) $this->integer('community_id');
    }

    public function rules(): array
    {
        $community = $this->communityId();
        $type = $this->input('location_type');
        // Cada id de ubicación debe existir dentro del mismo residencial del reporte.
        $inCommunity = fn (string $table) => Rule::exists($table, 'id')->where('community_id', $community);

        return [
            'community_id' => ['nullable', Rule::exists('communities', 'id')->where(fn ($q) => $q->whereIn('id', Community::visibleTo($this->user())->select('id')))],
            'title' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(Incident::TYPES)],
            'scope' => ['nullable', Rule::in(Incident::SCOPES)],
            'priority' => ['required', Rule::in(Incident::PRIORITIES)],
            'description' => ['nullable', 'string', 'max:2000'],
            'location_type' => ['required', Rule::in(array_keys(Incident::LOCATION_TYPES))],
            'block_id' => [Rule::requiredIf($type === 'block'), 'nullable', $inCommunity('blocks')],
            'building_id' => [Rule::requiredIf($type === 'building'), 'nullable', $inCommunity('buildings')],
            'street_id' => [Rule::requiredIf($type === 'street'), 'nullable', $inCommunity('streets')],
            'unit_id' => [Rule::requiredIf($type === 'apartment'), 'nullable', $inCommunity('units')],
            'reference' => [Rule::requiredIf($type === 'common_area'), 'nullable', 'string', 'max:160'],
            'evidence' => ['array', 'max:8'],
            'evidence.*' => ['file', 'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/webm', 'max:20480'],
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            // Como en el original, un residente solo reporta sobre apartamentos que son suyos.
            $unitId = $this->integer('unit_id');
            if ($this->input('location_type') === 'apartment' && $this->user()->isResident() && $unitId
                && ! $this->user()->units()->whereKey($unitId)->exists() && $this->user()->unit_id !== $unitId) {
                $validator->errors()->add('unit_id', 'Solo puedes reportar sobre tus propios apartamentos.');
            }
        }];
    }

    public function messages(): array
    {
        return ['evidence.*.mimetypes' => 'Solo se aceptan fotos (JPG, PNG, WebP) o videos (MP4, MOV, WebM).', 'evidence.*.max' => 'Cada archivo puede pesar hasta 20 MB.'];
    }
}
