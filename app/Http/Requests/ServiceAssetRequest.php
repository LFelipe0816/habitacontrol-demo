<?php

namespace App\Http\Requests;

use App\Models\ServiceAsset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // En la edición cada campo es opcional: mantenimiento solo envía lecturas y estado.
        $req = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'category' => [$req, 'string', 'max:60'],
            'name' => [$req, 'string', 'max:120'],
            'location' => ['sometimes', 'nullable', 'string', 'max:160'],
            'status' => ['sometimes', Rule::in(array_keys(ServiceAsset::STATUSES))],
            'health' => ['sometimes', 'integer', 'between:0,100'],
            'availability' => ['sometimes', 'nullable', 'numeric', 'between:0,100'],
            'capacity' => ['sometimes', 'nullable', 'string', 'max:80'],
            'reading_label' => ['sometimes', 'nullable', 'string', 'max:60'],
            'reading' => ['sometimes', 'nullable', 'integer', 'between:0,100'],
            'responsible' => ['sometimes', 'nullable', 'string', 'max:120'],
            'provider' => ['sometimes', 'nullable', 'string', 'max:120'],
            'routine' => ['sometimes', 'array', 'max:12'],
            'routine.*' => ['string', 'max:160'],
            'risk' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
