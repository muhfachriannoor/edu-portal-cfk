<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class OrderRequest extends FormRequest
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
            // Required fields
            // 'user_address_id' => ['required', 'integer', 'exists:user_addresses,id'],
            'channel_id'      => ['required', 'uuid', 'exists:channels,id'],
            // 'courier_id'      => ['required', 'uuid', 'exists:couriers,id'],
            'courier_type'    => ['required', 'exists:couriers,key'],
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
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed for order request.',
            'errors' => $validator->errors()
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}

