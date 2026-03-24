<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductRequest extends FormRequest
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
            // Detail tab
            'store_id' => ['nullable'],
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer'],
            'sub_category_id' => ['required', 'integer'],
            'brand_id' => ['required', 'integer'],
            'tags' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes'],
            'is_bestseller' => ['sometimes'],
            'is_truly_indonesian' => ['sometimes'],
            'is_limited_edition' => ['sometimes'],

            // If no variants
            'quantity'          => ['required_if:has_variants,0', 'integer', 'min:0'],
            'price'             => ['required_if:has_variants,0', 'required_with:discount_price', 'integer', 'min:0'],
            'sku'               => ['nullable'],
            'discount_period'   => ['required_with:discount_price'],
            'discount_price'    => ['required_with:discount_period', 'nullable', 'min:0', function ($attribute, $value, $fail) {
                    $price = (int) str_replace('.', '', request()->input('price'));

                    // Only validate comparison if price > 0
                    if ($price > 0 && $value >= $price) {
                        $fail("Discount price must be lower than price.");
                    }
                }
            ],
            
            // Images Tab
            'main_image_index' => ['sometimes'],
            'images' => ['sometimes'],

            // If has variants
            'options_json' => [
                function ($attribute, $value, $fail) {
                    if ($this->input('has_variants') == '1') {
                        if (empty($value) || !is_array($value) || count($value) < 1) {
                            $fail('Options are mandatory.');
                        }
                    }
                }
            ],
            'variants_json' => [
                function ($attribute, $value, $fail) {
                    if ($this->input('has_variants') == '1') {

                        if (empty($value) || !is_array($value) || count($value) < 1) {
                            return $fail('Variants are mandatory.');
                        }

                        foreach ($value as $index => $variant) {

                            $priceRaw    = trim($variant->price ?? '');
                            $discountRaw = trim($variant->discount_price ?? '');
                            $period      = trim($variant->discount_period ?? '');

                            // Normalize numbers (remove thousand separators)
                            $price    = (int) str_replace('.', '', $priceRaw);
                            $discount = (int) str_replace('.', '', $discountRaw);

                            // If one is filled, the other must be filled
                            if ($discountRaw !== '' && $discountRaw !== '0' && $period === '') {
                                return $fail("Variant #".($index + 1)." discount_period is required when discount_price is filled.");
                            }

                            if ($period !== '' && $discountRaw === '') {
                                return $fail("Variant #".($index + 1)." discount_price is required when discount_period is filled.");
                            }

                            // 🔴 MAIN RULE: discount_price must be lower than price
                            if ($discount > 0 && $discount >= $price) {
                                return $fail(
                                    "Variant #".($index + 1)." discount_price must be lower than price."
                                );
                            }

                            // if (!empty($variant->sku)) {

                            //     $exists = DB::table('product_variants')
                            //         ->where('sku', $variant->sku)
                            //         ->where('id', '!=', $variant->id ?? 0)
                            //         ->exists();

                            //     if ($exists) {
                            //         return $fail(
                            //             "Variant #".($index + 1)." SKU already exists."
                            //         );
                            //     }
                            // }
                        }
                    }
                }
            ],
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
            'quantity'              => (int) str_replace('.', '', request()->input('quantity')),
            'price'                 => (int) str_replace('.', '', request()->input('price')),
            'discount_price'        => (request()->input('discount_price')) ? (int) str_replace('.', '', request()->input('discount_price')) : null,
            'is_active'             => request()->has('is_active') ?? '0',
            'is_bestseller'         => request()->has('is_bestseller') ?? '0',
            'is_truly_indonesian'   => request()->has('is_truly_indonesian') ?? '0',
            'is_limited_edition'    => request()->has('is_limited_edition') ?? '0',
            'has_variants'          => request()->has('has_variants') ?? '0',
            'options_json'          => $this->convertJsonToArray($this->input('options_json')),
            'variants_json'         => $this->convertJsonToArray($this->input('variants_json'))
        ]);
    }

    /**
     * Convert JSON to Array.
     *
     */
    private function convertJsonToArray($json)
    {
        if (is_string($json)) {
            $decoded = json_decode($json);
            return is_array($decoded) ? $decoded : [];
        }

        return $json ?? [];
    }
}
