<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductOptionRequest extends FormRequest
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
            'image_text' => ['nullable', 'string', 'max:255'],
            'image' => ['sometimes', 'image', 'mimes:jpg,png,jpeg'],
            'options_json' => [
                function ($attribute, $value, $fail) {
                    if (empty($value) || !is_array($value) || count($value) < 1) {
                        $fail('Options are mandatory.');
                    }
                }
            ]
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
            'options_json' => $this->convertJsonToArray($this->input('options_json'))
        ]);
    }

    /**
     * Convert JSON to Array.
     *
     */
    private function convertJsonToArray($json)
    {
        if (is_string($json)) {
            return json_decode($json, true);
        }

        return $json ?? [];
    }
}
