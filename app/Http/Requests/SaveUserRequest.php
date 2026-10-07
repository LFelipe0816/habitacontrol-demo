<?php

namespace App\Http\Requests;

use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $target = $this->route('user');
        $create = $target === null;

        return [
            'name' => [$create ? 'required' : 'sometimes', 'string', 'max:120'],
            'email' => [$create ? 'required' : 'sometimes', 'email:rfc', 'max:160', Rule::unique('users', 'email')->ignore($target)],
            'password' => [$create ? 'required' : 'nullable', 'string', 'min:8', 'max:100'],
            'role' => [$create ? 'required' : 'sometimes', Rule::in($this->user()->assignableRoles())],
            'phone' => ['nullable', 'string', 'max:30'],
            'document_id' => ['nullable', 'string', 'max:30'],
            'active' => ['sometimes', 'boolean'],
            'units' => ['sometimes', 'array', 'max:10'],
            'units.*.unit_id' => ['required', Rule::exists('units', 'id')->where(fn ($q) => $q->whereIn('id', Unit::visibleTo($this->user())->select('id')))],
            'units.*.relation' => ['required', Rule::in(['propietario', 'residente', 'inquilino'])],
        ];
    }
}
