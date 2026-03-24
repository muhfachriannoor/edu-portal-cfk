<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Http\Response;

class UserInterestRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sub_category_ids' => 'required|array',
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
            'sub_category_ids.required' => 'The list of sub-category interests is required.',
            'sub_category_ids.*.integer' => 'Sub-category ID must be an integer.',
            'sub_category_ids.*.exists' => 'One of the sub-category IDs is invalid or not found.',
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
