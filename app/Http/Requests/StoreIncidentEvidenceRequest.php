<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncidentEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'collection' => ['required', Rule::in(['evidence', 'solution'])],
            'files' => ['required', 'array', 'min:1', 'max:8'],
            'files.*' => ['file', 'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/webm', 'max:20480'],
        ];
    }

    public function messages(): array
    {
        return ['files.*.mimetypes' => 'Solo se aceptan fotos (JPG, PNG, WebP) o videos (MP4, MOV, WebM).', 'files.*.max' => 'Cada archivo puede pesar hasta 20 MB.'];
    }
}
