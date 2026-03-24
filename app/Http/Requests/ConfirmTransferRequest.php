<?php

namespace App\Http\Requests;

use App\Models\Order;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class ConfirmTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sender_bank_name'    => trim((string) $this->input('sender_bank_name')),
            'sender_account_name' => trim((string) $this->input('sender_account_name')),
            'transfer_amount'     => (int) $this->input('transfer_amount'),
            'transfer_date'       => trim((string) $this->input('transfer_date')),
        ]);
    }

    public function rules(): array
    {
        return [
            'sender_bank_name'    => ['required', 'string', 'max:255'],
            'sender_account_name' => ['required', 'string', 'max:255'],
            'transfer_amount'     => ['required', 'integer', 'min:1'],
            'transfer_date'       => ['required', 'date_format:Y-m-d'],
            'receipt'             => ['required', 'file', 'mimes:png,jpg,jpeg,pdf', 'max:5120'],
        ];
    }

    /**
     * Return validation errors in JSON (API friendly).
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors'  => $validator->errors(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
