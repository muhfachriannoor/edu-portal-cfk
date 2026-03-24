<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class VoucherRequest extends FormRequest
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
        $voucherId = $this->route('coupon');
        $imageBaseRules = ['image', 'mimes:jpg,png,jpeg', 'max:2048'];
        
        return [
            'voucher_name_en' => ['required', 'string', 'max:200'],
            'voucher_name_id' => ['required', 'string', 'max:200'],
            'voucher_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('vouchers', 'voucher_code')->ignore($voucherId)
            ],
            'type' => [
                'required',
                Rule::in(['percentage', 'fixed_amount']),
            ],
            'amount' => ['required', 'integer', 'min:1'],
            'usage_limit' => ['required', 'integer', 'min:1'],
            'min_transaction_amount' => ['required', 'integer', 'min:0'],

            'max_discount_amount' => [
                'nullable',
                'integer',
                'min:5000',
                'required_if:type,percentage'
            ],

            'start_date' => ['required', 'date_format:Y-m-d H:i'],
            'end_date' => [
                'required',
                'date_format:Y-m-d H:i',
                'after_or_equal:start_date'
            ],
            'is_active' => ['required', 'boolean'],
            
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
        $voucherCode = $this->input('voucher_code');

        $this->merge([
            'voucher_code' => $voucherCode !== null
                ? strtoupper(trim($voucherCode))
                : $voucherCode,

            'is_active' => $this->has('is_active') ? $this->input('is_active') : '0',
            
            // Normalize money-like fields (keep null if not provided)
            'amount' => $this->filled('amount')
                ? (int) str_replace('.', '', $this->input('amount'))
                : null,

            'min_transaction_amount' => $this->filled('min_transaction_amount')
                ? (int) str_replace('.', '', $this->input('min_transaction_amount'))
                : null,

            'max_discount_amount' => $this->filled('max_discount_amount')
                ? (int) str_replace('.', '', $this->input('max_discount_amount'))
                : null,
        ]);
    }

    /**
     * Add additional conditional validation rules.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $type = $this->input('type');
            $amount = $this->input('amount');

            // Validasi range amount berdasarkan type
            if ($type === 'percentage' && $amount !== null) {
                $amountInt = (int) $amount;

                if ($amountInt < 1 || $amountInt > 100) {
                    $validator->errors()->add(
                        'amount',
                        'For percentage vouchers, the amount must be between 1 and 100.'
                    );
                }
            }

            if ($type === 'fixed_amount' && $amount !== null) {
                $amountInt = (int) $amount;

                if ($amountInt < 5000) {
                    $validator->errors()->add(
                        'amount',
                        'For fixed amount vouchers, the amount must be at least 5.000.'
                    );
                }
            }
        });
    }
}