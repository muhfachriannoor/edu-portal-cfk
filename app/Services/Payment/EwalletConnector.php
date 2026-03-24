<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Log;
use Xendit\Xendit;
use Xendit\EWallets;
use Xendit\Exceptions\ApiException;

class EwalletConnector implements ChannelConnector
{
    /**
     * @var array
     */
    private $params;

    public function requiredParams(): array
    {
        return ['external_id', 'code', 'phone', 'amount'];
    }

    public function setParams($params)
    {
        $countryCode = '+62';
        $internationalNumber = preg_replace('/^0/', $countryCode, $params['phone']);
        $properties = $params['code'] === 'OVO' ? ['mobile_number' => $internationalNumber] : [];

        $this->params = [
            'reference_id' => $params['external_id'],
            'channel_code' => 'ID_' . $params['code'],
            'amount' => $params['amount'],
            'channel_properties' => $properties,
            'currency' => 'IDR',
            'checkout_method' => 'ONE_TIME_PAYMENT',
        ];
    }

    public function getParams(): array
    {
       return $this->params;
    }

    /**
     * @return array
     */
    public function charge(): array
    {
        try {
            $response = EWallets::createEWalletCharge($this->params);
        } catch (ApiException $e) {
            $response = [
                'status' => 'FAILED',
                'http_code' => $e->getCode(),
                'error_code' => $e->getErrorCode(),
                'message' => $e->getMessage()
            ];
        }

        Log::channel('paymentapi')->info('Outgoing request: ');
        Log::channel('paymentapi')->info('Type: [EWALLET]');
        Log::channel('paymentapi')->info(json_encode($this->params, JSON_PRETTY_PRINT));
        Log::channel('paymentapi')->info('Response: ');
        Log::channel('paymentapi')->info(json_encode($response, JSON_PRETTY_PRINT));

        return $response;
    }
}
