<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class UserAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'receiver_name' => ['required', 'string', 'max:100'],
            'phone_number' => ['required', 'string', 'max:20'],
            'label' => ['nullable', 'string', 'max:100'],
            'address_line' => ['required', 'string', 'max:255'],

            'subdistrict_id' => ['required', 'string', 'exists:master_addresses,subdistrict_id'],

            'postal_code' => ['required', 'string', 'max:10'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
    
    /**
     * Display validation error messages in JSON format.
     * 
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @return void
     * 
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        // Throw an HttpResponseException to return a JSON response
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed.', // Generic error message
            'errors' => $validator->errors()
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
