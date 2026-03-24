<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreRequest extends FormRequest
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
            'name_en' => ['required', 'string', 'max:255'],
            'name_id' => ['required', 'string', 'max:255'],
            'description_id' => ['required', 'string', 'max:60000'],
            'description_en' => ['required', 'string', 'max:60000'],
            'slug' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'email' => ['required', 'string', 'max:255', 'email:rfc,dns'],
            'location_id' => ['required', 'string', 'max:255'],
            'is_verified' => ['required'],
            'is_delivery' => ['required'],
            'is_pickup' => ['required'],
            'is_active' => ['required'],
            'logo' => $isUpdate
                ? ['sometimes', 'image', 'mimes:jpg,png,jpeg']
                : ['required', 'image', 'mimes:jpg,png,jpeg'],
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
            'name_en.max' => 'The name may not be greated than :max characters',
            'name_id.max' => 'The name may not be greated than :max characters',
            'description_en.max' => 'The description may not be greated than :max characters',
            'description_id.max' => 'The description may not be greated than :max characters',
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
            'is_verified' => request()->has('is_verified') ?? '0',
            'is_active' => request()->has('is_active') ?? '0',
            'is_delivery' => request()->has('is_delivery') ?? '0',
            'is_pickup' => request()->has('is_pickup') ?? '0',
        ]);
    }
}
