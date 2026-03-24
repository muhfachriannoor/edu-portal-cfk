<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentCategoryRequest extends FormRequest
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
        $id = $this->route('paymentCategory');
        
        return [
            'name' => ['required', 'string', 'max:200', Rule::unique('channel_categories', 'name')->ignore($id)],
            'description' => ['nullable', 'string'],
            'is_enabled' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
            'code' => ['nullable', 'string'],
            'icon_image' => $isUpdate
                ? ['sometimes', 'image', 'mimes:jpg,png,jpeg', 'max:2048'] // 2MB
                : ['sometimes', 'image', 'mimes:jpg,png,jpeg', 'max:2048'], // 2MB
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
            'is_enabled' => $this->has('is_enabled') ? $this->input('is_enabled') : '0',
            'is_published' => $this->has('is_published') ? $this->input('is_published') : '0',

            'code' => strtoupper(str_replace(' ', '_', $this->input('name'))),
        ]);
    }
}
