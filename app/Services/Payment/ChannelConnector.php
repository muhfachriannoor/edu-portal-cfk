<?php

namespace App\Services\Payment;

interface ChannelConnector
{
    /**
     * @return array
     */
    public function requiredParams(): array;

    public function setParams($params);

    /**
     * @param $externalId
     * @param $amount
     * @return array
     */
    public function getParams(): array;

    /**
     * @return array
     */
    public function charge(): array;
}
