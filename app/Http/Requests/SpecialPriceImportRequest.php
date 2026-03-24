<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class SpecialPriceImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for the uploaded file.
     * 
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes: xlsx,xls,csv',
                'max:2048', // 2MB
            ],
        ];
    }

    /**
     * Custom validation messages.
     * 
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'File is required.',
            'file.file' => 'The uploaded file is invalid.',
            'file.mimes' => 'The file must be an Excel document (.xlsx, .xls, or .csv).',
            'file.max' => 'The file size must not exceed 2 MB.',
        ];
    }

    /**
     * Return consistent JSON shape for validation errors.
     * 
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidator(Validator $validator): void
    {
        $errors = collect($validator->errors()->toArray())
            ->map(function (array $messages, string $field) {
                return [
                    'row' => null,
                    'field' => $field,
                    'message' => $messages[0] ?? 'Invalid value.',
                ];
            })->values()->all();

        throw new HttpResponseException(
            response()->json([
                'message' => 'File validation failed.',
                'errors' => $errors,
            ], Response::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
