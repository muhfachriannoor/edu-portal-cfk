<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Milon\Barcode\Facades\DNS2DFacade;
use Xendit\Exceptions\ApiException;
use Xendit\QRCode;
// use DNS2D;

class QrisConnector implements ChannelConnector
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
        $hours = (int) ($params['expires_in_hours'] ?? 24);

        $this->params = [
            'reference_id' => $params['external_id'],
            'external_id'  => $params['external_id'],
            'amount'       => $params['amount'],
            'type'         => 'DYNAMIC',
            'api_version'  => '2022-07-31',
            'currency'     => 'IDR',
            'expires_at' => now('Asia/Jakarta')->addHours($hours)->toIso8601String(),
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
            $response = QRCode::create($this->params);

        } catch (ApiException $e) {
            $response = [
                'status' => 'FAILED',
                'http_code' => $e->getCode(),
                'error_code' => $e->getErrorCode(),
                'message' => $e->getMessage()
            ];
        }

        if (!array_key_exists('http_code', $response) && array_key_exists('qr_string', $response)) {
            $qrImage = DNS2DFacade::getBarcodePNG($response['qr_string'], 'QRCODE', 30, 33);

            // Encode to base64, add prefix 'data:image/png;base64,'
            $response['qr_image'] = 'data:image/png;base64,' . $qrImage;
        }

        Log::channel('paymentapi')->info('Outgoing request: ');
        Log::channel('paymentapi')->info('Type: [QRIS]');
        Log::channel('paymentapi')->info(json_encode($this->params, JSON_PRETTY_PRINT));
        Log::channel('paymentapi')->info('Response: ');
        Log::channel('paymentapi')->info(json_encode($response, JSON_PRETTY_PRINT));

        return $response;
    }
}
