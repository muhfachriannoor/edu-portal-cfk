<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class UpdateEmailPreferenceRequest extends FormRequest
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
        return [
            'brand_news_story' => 'boolean',
            'new_product_launch' => 'boolean',
            'back_in_stock_alert' => 'boolean',
            'order_account_update' => 'boolean',
            'wishlist_price_drop_alert' => 'boolean',
            'unsubscribe' => 'boolean',
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
