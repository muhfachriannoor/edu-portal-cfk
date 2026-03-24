<?php

namespace App\Services\Payment;

use App\Models\Channel;

class Qris extends Gateway
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
        parent::__construct();

        $this->paymentChannel = $paymentChannel;
    }

    /**
     * @return ChannelConnector
     */
    public function getChannelConnector(): ChannelConnector
    {
        return new QrisConnector();
    }

    /**
     * @return Channel
     */
    public function getModel(): Channel
    {
        return $this->paymentChannel;
    }
}
