<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BannerRequest extends FormRequest
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
        $id = $this->route('banner');
        $imageBaseRules = ['image', 'mimes:jpg,png,jpeg', 'max:2048'];

        $category = $this->input('category');

        // Default validatio rules for headline and subheadline
        $headlineRules = ['nullable', 'string'];
        $subheadlineRules = ['nullable', 'string'];

        if ($category === 'HOME_NOT_LOGGED') {
            $headlineRules = ['required', 'string'];
            $subheadlineRules = ['required', 'string'];
        }
        
        return [
            'name' => ['required', 'string', 'max:200', Rule::unique('banners', 'name')->ignore($id)],
            'category' => ['required', 'string'],
            'sequence' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
            'headline_en' => $headlineRules,
            'headline_id' => $headlineRules,
            'subheadline_en' => $subheadlineRules,
            'subheadline_id' => $subheadlineRules,
            'image' => array_merge(
                ['required'],
                $isUpdate ? ['sometimes'] : [],
                $imageBaseRules
            ),
        ];
    }
    
    /**
     * Preparation for validation.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'is_active' => $this->has('is_active') ? $this->input('is_active') : '0',

            // Normalize TinyMCE HTML output (unwrap single <p> wrapper if present)
            'headline_en' => $this->unwrapSingleP($this->input('headline_en')),
            'headline_id' => $this->unwrapSingleP($this->input('headline_id')),
            'subheadline_en' => $this->unwrapSingleP($this->input('subheadline_en')),
            'subheadline_id' => $this->unwrapSingleP($this->input('subheadline_id')),
        ]);
    }

    /**
     * Unwrap a single root <p>...</p> wrapper without breaking multi-block HTML.
     * - If the input is exactly one <p> wrapper, return its innerHTML.
     * - Otherwise return as-is.
     */
    private function unwrapSingleP(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') return '';

        // If it is exactly: <p ...>SOMETHING</p> (and nothing else), unwrap it
        if (preg_match('/^\s*<p\b[^>]*>(.*)<\/p>\s*$/is', $html, $m)) {
            return trim($m[1]);
        }

        return $html;
    }
}