<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
class CashOnDeliveryConnector implements ChannelConnector
{
    /**
     * @var array
     */
    private $params;

    public function requiredParams(): array
    {
        return ['external_id', 'amount'];
    }

    public function getParams(): array
    {
       return $this->params;
    }

    public function setParams($params)
    {
        $this->params = [
            'external_id' => $params['external_id'],
            'amount' => $params['amount'],
            'type' => 'DYNAMIC',
        ];
    }

    /**
     * @return array
     */
    public function charge(): array
    {

        if($this->params['amount'] > 0 ){
            return  ['status'=>'ACTIVE'];
        }else{
            return  ['status'=>'CAPTURED'];
        }

//        return $data;
    }
}
