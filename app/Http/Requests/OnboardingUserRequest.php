<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Http\Response;

class OnboardingUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Hanya user yang sedang login yang boleh mengubah minatnya sendiri
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     * NOTE:
     * - All fields are optional.
     * - Address is tereated as a block: if one field is filled, all must be filled.
     * 
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Profile + Address block:
            // If one is present, the others become required (via required_with).

            'name' => [
                'nullable',
                'string',
                'max:255',
                'required_with:language,subdistrict_id,address_line,phone_number,postal_code',
            ],
            'language' => [
                'nullable',
                'string',
                'max:10',
                'required_with:name,subdistrict_id,address_line,phone_number,postal_code',
            ],

            'subdistrict_id' => [
                'nullable',
                'string',
                'required_with:name,language,address_line,phone_number,postal_code',
            ],
            'address_line' => [
                'nullable',
                'string',
                'required_with:name,language,subdistrict_id,phone_number,postal_code',
            ],

            'phone_number' => [
                'nullable',
                'string',
                'max:15',
                'required_with:name,language,subdistrict_id,address_line,postal_code',
            ],
            'postal_code' => [
                'nullable',
                'string',
                'max:10',
                'required_with:name,language,subdistrict_id,address_line,phone_number',
            ],

            // Interests: optional.
            'sub_category_ids' => ['sometimes', 'array'],
            'sub_category_ids.*' => [
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) {
                    $query->whereNotNull('parent_id');
                }),
            ],
        ];
    }

    /**
     * Custom error messages for validation.
     * 
     * @return array
     */
    public function messages(): array
    {
        return [
            // Profile + address completeness
            'name.required_with' => 'The name is required when any address field (language, subdistrict, address line, phone number, or postal code) is provided.',
            'language.required_with' => 'The language is required when any address field (name, subdistrict, address line, phone number, or postal code) is provided.',
            'subdistrict_id.required_with' => 'The subdistrict ID is required when any address field (name, language, address line, phone number, or postal code) is provided.',
            'address_line.required_with' => 'The address line is required when any address field (name, language, subdistrict, phone number, or postal code) is provided.',
            'phone_number.required_with' => 'The phone number is required when any address field (name, language, subdistrict, address line, or postal code) is provided.',
            'postal_code.required_with' => 'The postal code is required when any address field (name, language, subdistrict, address line, or phone number) is provided.',
            
            // Validation for sub_category_ids
            'sub_category_ids.array' => 'The sub-category IDs must be an array.',
            'sub_category_ids.*.integer' => 'Each sub-category ID must be an integer.',
            'sub_category_ids.*.exists' => 'One of the sub-category IDs is invalid or not found.',
        ];
    }

    /**
     * Display validation error messages in JSON format.
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors'  => $validator->errors(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
