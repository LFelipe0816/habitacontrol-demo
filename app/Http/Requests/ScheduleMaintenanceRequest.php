<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScheduleMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scheduled_for' => ['required', 'date', 'after_or_equal:today'],
            'type' => ['required', Rule::in(['preventivo', 'correctivo'])],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
