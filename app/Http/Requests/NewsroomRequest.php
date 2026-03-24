<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Newsroom;
use Illuminate\Validation\Validator;

class NewsroomRequest extends FormRequest
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
        $newsroomInstance = $this->route('newsroom'); 
        
        $slugRule = Rule::unique('newsroom', 'slug');

        if ($isUpdate && $newsroomInstance instanceof Newsroom) {
             $slugRule->ignore($newsroomInstance->id);
        }

        $imageRule = $isUpdate
            ? ['sometimes', 'image', 'mimes:jpg,png,jpeg', 'max:2048'] // 2MB
            : ['required', 'image', 'mimes:jpg,png,jpeg', 'max:2048'];

        $publishedAtRule = $this->input('is_active') 
            ? 'required|date|date_format:Y-m-d' 
            : 'nullable|date|date_format:Y-m-d';

        return [
            // --- CORE FIELDS ---
            'slug' => ['required', 'string', 'max:255', $slugRule],
            'is_active' => 'nullable|boolean',
            'published_at' => $publishedAtRule,
            'image' => $imageRule, 

            // --- SEO FIELDS PER LOCALE ---
            'meta_title_en' => 'nullable|string|max:255',
            'meta_title_id' => 'nullable|string|max:255',
            'meta_description_en' => 'nullable|string',
            'meta_description_id' => 'nullable|string',
            'meta_keywords_en' => 'nullable|string|max:255',
            'meta_keywords_id' => 'nullable|string|max:255',

            // --- TRANSLATION FIELDS (Title & Content) ---
            'title_en' => 'required|string|max:255',
            'title_id' => 'required|string|max:255',
            'content_en' => 'required|string',
            'content_id' => 'required|string',
        ];
    }
    
    /**
     * Preparation for validation.
     * Mengubah nilai checkbox 'is_active' yang tidak terkirim menjadi '0'.
     */
    protected function prepareForValidation()
    {
        $sanitized = [];
        foreach (['meta_description_en', 'meta_description_id'] as $field) {
            if ($this->has($field)) {
                $sanitized[$field] = strip_tags($this->input($field));
            }
        }

        $this->merge(array_merge($sanitized, [
            'is_active' => $this->has('is_active') ? '1' : '0',
        ]));
    }
}