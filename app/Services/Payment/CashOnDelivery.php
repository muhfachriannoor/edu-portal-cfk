<?php

namespace App\Services\Payment;

use App\Models\Channel;

class CashOnDelivery extends Gateway
{
    /**
     * @var Channel
     */
    private $paymentChannel;

    /**
     * Qris constructor.
     * @param Channel $paymentChannel
     */
    public function __construct(Channel $paymentChannel)
    {
        $this->paymentChannel = $paymentChannel;
    }

    /**
     * @return ChannelConnector
     */
    public function getChannelConnector(): ChannelConnector
    {
        return new CashOnDeliveryConnector;
    }

    /**
     * @return Channel
     */
    public function getModel(): Channel
    {
        return $this->paymentChannel;
    }
}
