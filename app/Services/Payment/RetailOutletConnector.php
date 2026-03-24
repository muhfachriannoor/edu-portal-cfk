<?php

namespace App\Services\Payment;

use App\Models\Channel;
use Illuminate\Support\Facades\Log;
use Xendit\Xendit;
use Xendit\Retail;
use Xendit\Exceptions\ApiException;

class RetailOutletConnector implements ChannelConnector
{
    /**
     * @var array
     */
    private $params;

    public function requiredParams(): array
    {
        return ['external_id', 'code', 'amount'];
    }

    public function setParams($params)
    {
        $this->params = [
            'external_id' => $params['external_id'],
            'retail_outlet_name' => $params['code'],
            'name' => $params['name'],
            'expected_amount' => $params['amount'],
            'is_single_use' => true,
            'expiration_date' => !empty($params['expires_in_hours']) ?
                                  now('UTC')->addHours($params['expires_in_hours'])->toIso8601String() :
                                  now('Asia/Jakarta')->addHours(24)->toIso8601String(),
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
            $response = Retail::create($this->params);
        } catch (ApiException $e) {
            $response = [
                'status' => 'FAILED',
                'http_code' => $e->getCode(),
                'error_code' => $e->getErrorCode(),
                'message' => $e->getMessage()
            ];
        }

        Log::channel('paymentapi')->info('Outgoing request: ');
        Log::channel('paymentapi')->info('Type: [RETAILOUTLET]');
        Log::channel('paymentapi')->info(json_encode($this->params, JSON_PRETTY_PRINT));
        Log::channel('paymentapi')->info('Response: ');
        Log::channel('paymentapi')->info(json_encode($response, JSON_PRETTY_PRINT));

        return $response;
    }
}
