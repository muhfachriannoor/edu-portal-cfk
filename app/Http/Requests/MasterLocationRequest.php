<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MasterLocationRequest extends FormRequest
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
        $masterLocationId = $this->route('masterLocation');

        return [
            'location' => [
                'required',
                'max:200',
                Rule::unique('master_location', 'location')->ignore($masterLocationId),
            ],
            'city' => [
                'required',
                'string',
                'max:100',
            ],
            'type_label' => [
                'required',
                'string',
                'max:100',
            ],
            'location_path_api' => [
                'required',
                'string',
                'max:100',
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('master_location', 'slug')->ignore($masterLocationId),
            ],
            'master_address_id' => ['required', 'string'],
            'address' => ['required', 'string'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'warning_pickup' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'point_operational_en' => ['required', 'string', 'max:200'],
            'point_operational_id' => ['required', 'string', 'max:200'],
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
        $address = $this->input('address');

        $this->merge([
            // 1. Logic untuk 'is_active' (logic yang sudah ada)
            // Jika checkbox 'is_active' tidak dikirim, merge nilainya menjadi '0'.
            'warning_pickup' => $this->has('warning_pickup') ? $this->input('warning_pickup') : '0',
            'is_active' => $this->has('is_active') ? $this->input('is_active') : '0',

            // 2. Logic untuk 'meta_description' (sanitasi)
            // Gunakan strip_tags() hanya jika input tidak NULL, untuk menghindari error.
            'address' => ($address !== null) 
                ? strip_tags($address) 
                : $address, 
        ]);
    }
}
