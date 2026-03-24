<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\Channel;
use App\Models\ChannelSetting;
use App\Models\Gateway as ModelsGateway;
use App\Models\PaymentSetting;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Xendit\Xendit;
use Log;

abstract class Gateway
{
    /**
     * @var string
     */
    protected $status;

    /**
     * @return ChannelConnector
     */
    abstract public function getChannelConnector(): ChannelConnector;

    /**
     * @return Channel
     */
    abstract public function getModel(): Channel;

    public function __construct()
    {
        $gateway = ModelsGateway::where('code', 'XENDIT')->first();
        $key = $gateway->settings()->where('meta', 'SECRET_KEY')->first();
        Xendit::setApiKey($key->value);
    }

    public function run($params)
    {
        $amount = $params['amount'];
        $params['amount'] = $this->getCost($params['amount'], $this->getModel());
        $channel = $this->getChannelConnector();
        $this->validate($channel->requiredParams(), $params);
        $channel->setParams($params);
        $response = $channel->charge();
        $payment = Payment::where('code', $params['external_id'])->first();

        $payment->fill([
            'extra' => $params['amount'] - $amount,
        ]);

        // Log::warning($params);
        // $payment->transaction->payloads()->create([
        //     'ip_address' => 'XENDIT',
        //     'user_agent' => 'API XENDIT',
        //     'code' => 200,
        //     'payload_type' => 'RESPONSE',
        //     'header' => '',
        //     'body' => json_encode($response),
        // ]);

        return $response;
    }

    protected function validate($requiredParams, $params)
    {
        if (!Arr::has($params, $requiredParams))
            throw new \Exception('Required parameter is ' . implode(', ', $requiredParams) . '.');
    }

    /**
     * @param $amount
     * @return float|int
     */
    protected function getCost($amount, $model)
    {
        $amount += $model->cost || $model->cost == 0  ? $model->cost : (PaymentSetting::where('meta', 'DEFAULT_COST')->first())->value;
        if ($amount < $model->minimum_amount)
            throw new Exception('Payment failed, your amount is less than minimum amount');

        $amount = $amount + ($amount * (PaymentSetting::where('meta', 'DEFAULT_TAX')->first())->value);
        return ceil($amount);
    }

    /**
     * @throws \Exception
     * @return void
     */
    protected function throwWhenPaymentFailed(): void
    {
        if ($this->status === 'FAILED')
            throw new Exception('Payment failed.');
    }
}
