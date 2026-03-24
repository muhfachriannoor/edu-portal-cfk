<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CourierRequest extends FormRequest
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
            'name_en'           => ['required', 'string', 'max:200'],
            'name_id'           => ['required', 'string', 'max:200'],
            'description_en'    => ['nullable', 'string'],
            'description_id'    => ['nullable', 'string'],
            'fee'               => ['required', 'integer'],
            'key'               => ['required', 'string', 'max:20'],
            'is_pickup'         => ['nullable', 'boolean'],
            'is_active'         => ['nullable', 'boolean'],
        ];
    }
    
    /**
     * Preparation for validation.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'is_pickup' => $this->has('is_pickup') ? $this->input('is_pickup') : '0',
            'is_active' => $this->has('is_active') ? $this->input('is_active') : '0',
            'fee'       => $this->has('has_fee') ?
                (int) str_replace('.', '', $this->input('fee')) : 0
        ]);
    }
}