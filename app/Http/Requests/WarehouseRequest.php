<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WarehouseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        $warehouseId = $this->route('warehouse');
        
        return [
            'name' => [
                'required',
                'max:200',
                Rule::unique('warehouses', 'name')->ignore($warehouseId),
            ],
            'address' => ['required','string'],
            'master_address_id' => ['required','string'],
            'postal_code' => ['required','string'],
            'is_active' => ['nullable', 'boolean']
        ];
    }

    public function messages(): array
    {
        return [
            
        ];
    }

    /**
     * Preparation for validation.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'is_active' => request()->has('is_active') ?? '0'
        ]);
    }
}
