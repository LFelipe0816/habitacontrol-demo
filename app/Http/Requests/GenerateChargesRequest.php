<?php

namespace App\Http\Requests;

use App\Models\Community;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateChargesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'community_id' => ['required', Rule::exists('communities', 'id')->where(fn ($q) => $q->whereIn('id', Community::visibleTo($this->user())->select('id')))],
            'concept' => ['required', 'string', 'max:120'],
            'due_date' => ['required', 'date'],
        ];
    }
}
