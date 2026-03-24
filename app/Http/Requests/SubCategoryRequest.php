<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubCategoryRequest extends FormRequest
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
        $subCategoryId = $this->route('subCategory');
        $categoryId = $this->input('category_id');

        return [
            'category_id' => 'required|integer|exists:categories,id',
            'name_en' => ['required', 'string', 'max:200'],
            'name_id' => ['required', 'string', 'max:200'],
            'description_en' => ['nullable', 'string'],
            'description_id' => ['nullable', 'string'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('sub_categories', 'slug')
                    ->where(function ($query) use ($categoryId) {
                        return $query->where('category_id', $categoryId);
                    })
                    ->ignore($subCategoryId),
            ],
            'order' => 'required|integer|min:0',
            'is_active' => 'nullable|boolean',
            'image' => $isUpdate
                ? ['sometimes', 'image', 'mimes:jpg,png,jpeg', 'max:2048'] // 2MB
                : ['required', 'image', 'mimes:jpg,png,jpeg', 'max:2048'], // 2MB
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
        $this->merge([
            'is_active' => request()->has('is_active') ?? '0'
        ]);
    }
}
