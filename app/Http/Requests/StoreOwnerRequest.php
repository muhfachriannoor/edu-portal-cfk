<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreOwnerRequest extends FormRequest
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
            'name' => ['required', 'max:200'],
            'email' => ['required', 'email', 'max:200', 'unique:store_owners,email'],
            'role' => ['required'],
            'password' => $isUpdate
                ? ['sometimes', 'confirmed']
                : ['required', 'min:1', 'confirmed'],                
            'is_active' => ['boolean'],
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
