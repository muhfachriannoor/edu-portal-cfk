<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BrandRequest extends FormRequest
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
        $brandId = $this->route('brand');
        
        return [
            'name' => [
                'required',
                'max:200',
                Rule::unique('brands', 'name')->ignore($brandId),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('brands', 'slug')->ignore($brandId),
            ],
            'is_active' => ['nullable', 'boolean'],
            'logo' => $isUpdate
                ? ['sometimes', 'image', 'mimes:jpg,png,jpeg', 'max:2048'] // 2MB
                : ['required', 'image', 'mimes:jpg,png,jpeg', 'max:2048'],
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
