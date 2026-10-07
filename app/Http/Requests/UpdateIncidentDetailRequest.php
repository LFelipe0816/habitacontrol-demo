<?php

namespace App\Http\Requests;

use App\Models\Incident;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIncidentDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'priority' => ['sometimes', Rule::in(Incident::PRIORITIES)],
            'admin_notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'resolution' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'recurrence_note' => ['sometimes', 'nullable', 'string', 'max:255'],
            'recurring' => ['sometimes', 'boolean'],
        ];
    }
}
