<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SpecialPriceRequest extends FormRequest
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

        return [
            'product_variant_id' => ['required', 'integer'],
            'type' => ['required'],
            'discount' => ['required_if:type,discount', 'integer'],
            'percentage' => ['nullable','required_if:type,percentage', 'numeric', 'between:0,100', 'regex:/^\d{1,3}(\.\d{1,2})?$/'],
            'period' => ['required'],
            'is_active' => ['sometimes', 'boolean']
        ];
    }

    /**
     * Custom messages if validation failed.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
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
            'discount' => (int) str_replace('.', '', request()->input('discount')),
            'is_active' => request()->has('is_active') ?? '0'
        ]);
    }
}
