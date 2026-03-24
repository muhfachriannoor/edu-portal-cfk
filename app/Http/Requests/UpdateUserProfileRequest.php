<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class UpdateUserProfileRequest extends FormRequest
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
        $userId = auth()->id();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'mobile_number' => ['nullable', 'regex:/^[0-9]{10,15}$/'],
            'title' => ['nullable', 'string', Rule::in(['Mr', 'Mrs', 'Ms', 'Dr', 'Other'])],
            'date_of_birth' => [
                'nullable', 
                'date', 
                'before_or_equal:2015-12-31'
            ],
            'language' => ['nullable', 'string', 'max:10'],
            'location' => ['nullable', 'string', 'max:255'],

            'sub_category_ids' => ['nullable', 'array'],

            'sub_category_ids.*.id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) {
                    $query->whereNotNull('parent_id');
                }),
            ],

            'sub_category_ids.*.name' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * Custom error messages for multi-language support.
     */
    public function messages(): array
    {
        return [
            'date_of_birth.before_or_equal' => trans('api.profile.dob_limit'),
        ];
    }

    /**
     * Display validation error messages in JSON format.
     * 
     * @param \Illuminates\Contracts\Validation\Validator $validator
     * @return void
     * 
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        // Throw an HttpResponseException to return a JSON response
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $validator->errors(),     
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
