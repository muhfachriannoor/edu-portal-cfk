<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class XenditPaymentService
{
    private string $secretKey;

    /** @var string[] */
    private array $successStatuses = [
        'SUCCEEDED',
        'COMPLETED',
        'CAPTURED',
        'SUCCESS',
        'PAID',
        'SETTLED',
    ];

    /** @var string[] */
    private array $failStatuses = [
        'FAILED',
        'EXPIRED',
    ];

    /** @var string[] */
    private array $pendingStatuses = [
        'WAITING',
        'ACTIVE',
        'PENDING',
        'REQUIRES_ACTION',
    ];

    public function __construct()
    {
        $this->secretKey = (string) config('services.default.xendit.key.secret');
    }

    /**
     * Normalize payment_details into array.
     * Support JSON string, array, or null.
     */
    public function normalizePaymentDetails(mixed $paymentDetails): array
    {
        if (is_array($paymentDetails)) {
            return $paymentDetails;
        }

        if (is_string($paymentDetails) && $paymentDetails !== '') {
            $decoded = json_decode($paymentDetails, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * Best-effort invalidation for legacy instruments:
     * - Fixed VA: expire now via PATCH /callback_virtual_accounts/{id}
     * - QR dynamic (QR Codes v1): no void/cancel endpoint -> handled by callback guard
     */
    public function invalidateOnCancelOrChange(Payment $payment): void
    {
        $details = $this->normalizePaymentDetails($payment->payment_details);
        $this->expireFixedVaNowBestEffort($details);
    }

    /**
     * Best-effort invalidation using old payment_details snapshot
     * (useful when you overwrite payment_details on change payment).
     */
    public function invalidateFromOldDetails(array $oldDetails): void
    {
        $this->expireFixedVaNowBestEffort($oldDetails);
    }

    /**
     * Minimal callback guard: ignore callback if:
     * - order/payment already cancelled
     * - callback is for stale instrument (VA/QR ID not matching current payment_details.id)
     * 
     * Returns: [ignore(bool), reason(string)]
     */
    public function shouldIgnoreCallback(Request $request, Payment $payment, ?Order $order): array
    {
        // Guard A: never resurrect cancelled state
        if (($payment->status ?? null) === 'CANCELLED') {
            return [true, 'payment_cancelled'];
        }

        if ($order && (int) ($order->status ?? 0) === 99) {
            return [true, 'order_cancelled'];
        }

        // Guard B: stale callback check based on instrument id
        $storedDetails = $this->normalizePaymentDetails($payment->payment_details);
        $storedInstrumentId = (string) data_get($storedDetails, 'id', '');

        // If we cannot identify stored instrument, we cannot do stale protection reliably.
        // In that case, do NOT ignore here; let status mapping decide.
        if ($storedInstrumentId === '') {
            return [false, 'no_stored_instrument_id'];
        }

        $incomingVaId = $request->input('callback_virtual_account_id');
        if ($incomingVaId) {
            if ((string) $incomingVaId !== $storedInstrumentId) {
                return [true, 'stale_va_callback'];
            }
        }

        $incomingQrId = $request->input('qr_code.id'); // QR Codes v1 callback payload commonly includes qr_code.id
        if ($incomingQrId) {
            if ((string) $incomingQrId !== $storedInstrumentId) {
                return [true, 'stale_qr_callback'];
            }
        }

        return [false, 'ok'];
    }

    /**
     * Interpret gateway status into internal state: SUCCEEDED / FAILED / PENDING / UNKNOWN
     */
    public function mapCallbackStatus(Request $request): string
    {
        $status = strtoupper((string) $request->input('status', ''));

        // For Fixed VA, some callback may not carry status consistently.
        // If callback_virtual_account_id exists and status is empty, we treat it as SUCCEEDED
        $hasVaCallback = (bool) $request->input('callback_virtual_account_id');

        if ($hasVaCallback && $status === '') {
            return 'SUCCEEDED';
        }

        if ($status !== '' && in_array($status, $this->successStatuses, true)) {
            return 'SUCCEEDED';
        }

        if ($status !== '' && in_array($status, $this->failStatuses, true)) {
            return 'FAILED';
        }

        if ($status !== '' && in_array($status, $this->pendingStatuses, true)) {
            return 'PENDING';
        }

        return $status !== '' ? $status : 'UNKNOWN';
    }

    /**
     * Fixed VA is NOT cancellable, but can be inactivated by updating expiration_date.
     * Best-effort: does not throw; logs on failure.
     */
    private function expireFixedVaNowBestEffort(array $details): void
    {
        $id = data_get($details, 'id');
        $accountNumber = data_get($details, 'account_number');
        $bankCode = data_get($details, 'bank_code');

        // Heuristic: Fixed VA payload includes account_number + bank_code and UUID id
        if (!$id || !$accountNumber || !$bankCode) {
            return;
        }

        try {
            $payload = [
                // Use UTC ISO-8601 Z
                'expiration_date' => now()->utc()->format('Y-m-d\TH:i:s.000\Z'),
            ];

            // Some Xendit docs show expected_amount together with expiration_date update
            $expectedAmount = data_get($details, 'expected_amount');
            if ($expectedAmount !== null) {
                $payload['expected_amount'] = (int) $expectedAmount;
            }

            Http::withBasicAuth($this->secretKey, '')
                ->acceptJson()
                ->asJson()
                ->patch("https://api.xendit.co/callback_virtual_accounts/{$id}", $payload)
                ->throw();
        } catch (\Throwable $e) {
            Log::warning('Xendit Fixed VA expire failed (best-effort).', [
                'xendit_va_id' => $id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}