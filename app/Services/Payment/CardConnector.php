<?php

namespace App\Services\Payment;

use App\Models\Gateway;
use App\Models\GatewaySetting;
use Illuminate\Support\Facades\Log;
use Xendit\Xendit;
use Xendit\Cards;
use Xendit\Exceptions\ApiException;

class CardConnector implements ChannelConnector
{
    /**
     * @var array
     */
    private $params;

    public function requiredParams(): array
    {
        return ['external_id', 'token_id', 'authentication_id', 'amount'];
    }

    public function getParams(): array
    {
       return $this->params;
    }

    public function setParams($params)
    {
        $this->params = [
            'external_id' => $params['external_id'],
            'token_id' => $params['token_id'],
            'authentication_id' => $params['authentication_id'],
            'amount' => $params['amount'],
        ];
    }

    /**
     * @return array
     */
    public function charge(): array
    {
        try {
            $response =  Cards::create($this->params);
        } catch (ApiException $e) {
            $response = [
                'status' => 'FAILED',
                'http_code' => $e->getCode(),
                'error_code' => $e->getErrorCode(),
                'message' => $e->getMessage()
            ];
        }

        Log::channel('paymentapi')->info('Outgoing request: ');
        Log::channel('paymentapi')->info('Type: [CARD]');
        Log::channel('paymentapi')->info(json_encode($this->params, JSON_PRETTY_PRINT));
        Log::channel('paymentapi')->info('Response: ');
        Log::channel('paymentapi')->info(json_encode($response, JSON_PRETTY_PRINT));

        return $response;
    }

    /**
     * @return array
     */
    protected function createResponse(): array
    {
        return Cards::create($this->params);

        if (app()->environment(['staging', 'production'])) {
            return Cards::create($this->params);
        }

        $dummy = '{
    "created": "2020-01-11T07:33:14.442Z",
    "status": "CAPTURED",
    "business_id": "5850e55d8d9791bd40096364",
    "authorized_amount": 900000,
    "external_id": "10000",
    "merchant_id": "xendit",
    "merchant_reference_code": "598d5d0d51e0870d44c61533",
    "card_type": "CREDIT",
    "masked_card_number": "400000XXXXXX0002",
    "charge_type": "SINGLE_USE_TOKEN",
    "card_brand": "VISA",
    "bank_reconciliation_id": "5132390610356134503009",
    "eci": "05",
    "capture_amount": 900000,
    "descriptor": "XENDIT*MYBUSINESS-MY NEW STORE",
    "id": "598d5dba51e0870d44c61539",
    "mid_label": "IDR_MID",
    "promotion": {
        "reference_id": "BCA_10",
        "original_amount": "1000000"
    },
    "installment": {
        "count": 3,
        "interval": "month"
    }
}';
        return json_decode($dummy, true);
    }
}
