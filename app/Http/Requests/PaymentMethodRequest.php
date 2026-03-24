<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentMethodRequest extends FormRequest
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
        $id = $this->route('paymentMethod');
        
        return [
            'name' => ['required', 'string', 'max:200', Rule::unique('channel_categories', 'name')->ignore($id)],
            'code' => ['required', 'string'],
            'channel_category_id' => ['required', 'string', 'exists:channel_categories,id'],
            'description' => ['nullable', 'string'],
            'expires_in_hours' => ['required', 'integer', 'min:1'],
            'minimum_amount' => ['required', 'integer', 'min:0'],
            'cost' => ['required', 'integer', 'min:0'],
            'account_name' => [
                Rule::requiredIf($this->is_manual == '1'),
                'nullable', 'string'
            ],
            'account_number' => [
                Rule::requiredIf($this->is_manual == '1'),
                'nullable', 'string'
            ],
            'is_enabled' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'is_manual' => ['nullable', 'boolean'],
            'channel_image' => $isUpdate
                ? ['sometimes', 'image', 'mimes:jpg,png,jpeg', 'max:2048'] // 2MB
                : ['sometimes', 'image', 'mimes:jpg,png,jpeg', 'max:2048'], // 2MB
        ];
    }

    public function messages(): array
    {
        return [
            'account_name.required_with' => 'The Account Name is required when any manual field (Account Number, and Is Manual) is provided.',
            'account_number.required_with' => 'The Account Number is required when any manual field (Account Name, and Is Manual) is provided.',
            'is_manual.required_with' => 'The Is Manual is required when any manual field (Account Name, and Account Number) is provided.',
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
            'is_enabled' => $this->has('is_enabled') ? $this->input('is_enabled') : '0',
            'is_published' => $this->has('is_published') ? $this->input('is_published') : '0',
            'is_manual' => $this->has('is_manual') ? $this->input('is_manual') : '0',

            'minimum_amount' => (int) str_replace('.', '', request()->input('minimum_amount')),
            'cost' => (int) str_replace('.', '', request()->input('cost')),

            'code' => strtoupper($this->input('code')),
        ]);
    }
}
