<?php

namespace App\Services\Payment;

use App\Models\Channel;

class RetailOutlet extends Gateway
{
    /**
     * @var Channel
     */
    private $paymentChannel;

    /**
     * @var array
     */
    private $params;

    /**
     * RetailOutlet constructor.
     * @param Channel $paymentChannel
     * @param array $params
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
        return new RetailOutletConnector($this->params);
    }

    /**
     * @return Channel
     */
    public function getModel(): Channel
    {
        return $this->paymentChannel;
    }
}
