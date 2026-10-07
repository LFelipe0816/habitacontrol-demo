<?php

namespace App\Http\Requests;

use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordPaymentRequest extends FormRequest
{
    public const METHODS = ['Transferencia', 'Efectivo', 'Tarjeta', 'Cheque'];

    public function authorize(): bool
    {
        return true;
    }

    /** `unit_id` solo viaja en el pago a nivel de unidad; el pago de un cobro ya conoce su unidad. */
    public function rules(): array
    {
        return [
            'unit_id' => [Rule::requiredIf($this->route('charge') === null), 'nullable', Rule::exists('units', 'id')->where(fn ($q) => $q->whereIn('id', Unit::visibleTo($this->user())->select('id')))],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999'],
            'method' => ['required', Rule::in(self::METHODS)],
            'reference' => ['nullable', 'string', 'max:60'],
        ];
    }
}
