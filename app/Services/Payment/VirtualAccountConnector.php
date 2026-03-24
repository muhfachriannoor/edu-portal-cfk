<?php

namespace App\Services\Payment;

use App\Models\Channel;
use App\Models\Gateway;
use Illuminate\Support\Facades\Log;
use Xendit\Xendit;
use Xendit\VirtualAccounts;
use Xendit\Exceptions\ApiException;

class VirtualAccountConnector implements ChannelConnector
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
        // $params = [
        //     'external_id' => $externalId,
        //     'bank_code' => $this->params['code'],
        //     'name' => $this->params['name'] ?? 'Unictive',
        //     'expected_amount' => $amount,
        //     'expiration_date' => !empty($this->params['expires_in_hours']) ? now('Asia/Jakarta')->addHours($this->params['expires_in_hours'])->toIso8601String() : now('Asia/Jakarta')->addHours(24)->toIso8601String(),
        //     'is_closed' => true,
        //     'is_single_use' => true,
        // ];

        $hours = (int) ($params['expires_in_hours'] ?? 24);

        $this->params = [
            'external_id'      => $params['external_id'],
            'bank_code'        => $params['code'],
            'is_closed'        => true,
            'name'             => $params['name'] ?? 'Unictive Payment',
            'expected_amount'  => $params['amount'],
            'expiration_date'  => now('Asia/Jakarta')->addHours($hours)->toIso8601String(),
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
            $response = VirtualAccounts::create($this->params);

        } catch (ApiException $e) {
            $response = [
                'status' => 'FAILED',
                'http_code' => $e->getCode(),
                'error_code' => $e->getErrorCode(),
                'message' => $e->getMessage()
            ];
        }

        Log::channel('paymentapi')->info('Outgoing request: ');
        Log::channel('paymentapi')->info('Type: [VIRTUALACCOUNT]');
        Log::channel('paymentapi')->info(json_encode($this->params, JSON_PRETTY_PRINT));
        Log::channel('paymentapi')->info('Response: ');
        Log::channel('paymentapi')->info(json_encode($response, JSON_PRETTY_PRINT));

        return $response;
    }
}
