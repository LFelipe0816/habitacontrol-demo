<?php

namespace App\Http\Requests;

use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'unit_id' => ['required', Rule::exists('units', 'id')->where(fn ($q) => $q->whereIn('id', Unit::visibleTo($this->user())->select('id')))],
            'concept' => ['required', 'string', 'max:120'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999'],
            'due_date' => ['required', 'date'],
        ];
    }
}
