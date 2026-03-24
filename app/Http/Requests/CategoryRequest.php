<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
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
        $categoryId = $this->route('category');
        
        return [
            'name_en' => ['required', 'string', 'max:200'],
            'name_id' => ['required', 'string', 'max:200'],
            'description_en' => ['required', 'string'],
            'description_id' => ['required', 'string'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($categoryId),
            ],
            'order' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_navbar' => ['nullable', 'boolean'],
            
            'image' => $isUpdate
                ? ['sometimes', 'image', 'mimes:jpg,png,jpeg', 'max:2048'] // 2MB
                : ['required', 'image', 'mimes:jpg,png,jpeg', 'max:2048'], // 2MB

            'icon_image' => $isUpdate
                ? ['sometimes', 'image', 'mimes:jpg,png,jpeg', 'max:2048'] // 2MB
                : ['sometimes', 'image', 'mimes:jpg,png,jpeg', 'max:2048'], // 2MB

            'parent_id' => [
                'nullable',
                'exists:categories,id',
            ],

            // --- SEO FIELDS PER LOCALE ---
            'meta_title_en' => 'nullable|string|max:255',
            'meta_title_id' => 'nullable|string|max:255',
            'meta_description_en' => 'nullable|string',
            'meta_description_id' => 'nullable|string',
            'meta_keywords_en' => 'nullable|string|max:255',
            'meta_keywords_id' => 'nullable|string|max:255',
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
        // Sanitasi semua description dan meta description dari tag HTML
        $fieldsToStrip = [
            'description_en', 'description_id', 
            'meta_description_en', 'meta_description_id'
        ];

        $sanitized = [];
        foreach ($fieldsToStrip as $field) {
            if ($this->has($field)) {
                $sanitized[$field] = strip_tags($this->input($field));
            }
        }

        $this->merge(array_merge($sanitized, [
            'is_active' => $this->has('is_active') ? '1' : '0',
            'is_navbar' => $this->has('is_navbar') ? '1' : '0',
        ]));
    }
}
