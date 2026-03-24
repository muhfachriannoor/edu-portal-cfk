<?php
namespace App\Services;

use App\Models\Order;
use App\Models\LexLog;
use App\View\Data\CourierData;
use Illuminate\Support\Carbon;

trait LazadaApi {
    public function createPackage($order_id, $isDebug = false){
        $order = Order::find($order_id);

        if($order->courier?->key == 'delivery-lazada'){
            require_once base_path('resources/sdk/Lazop/LazopSdk.php');

            // Data
            $user = auth()->user();
            $locale = app()->getLocale();

            // Config
            $seller_id = config('lazop.seller_id');
            $platform_name = config('lazop.platform_name');
            $warehouse_name = config('lazop.warehouse_name');
            $warehouse_code = config('lazop.warehouse_code');

            $url = config('lazop.url');
            $app_key = config('lazop.app_key');
            $secret_key = config('lazop.secret_key');

            // Data
            $store = $order->store;
            $userAddress = $order->userAddress;
            $orderItems = $order->orderItems->map(function($item){
                return [
                    'unitPrice' => $item->base_price,
                    'quantity' => $item->quantity,
                    'dimWeight' => [
                        'length' => 10,
                        'width' => 10,
                        'weight' => 100,
                        'height' => 10,
                    ],
                    'name' => $item->product_name,
                    'id' => $item->sarinah_product_id,
                    'sku' => $item->productVariant?->sku,
                    'category' => "",
                    'paidPrice' => $item->selling_price
                ];
            });
            $orderTotal = collect($orderItems)->sum(function ($item) {
                return $item['unitPrice'] * $item['quantity'];
            });

            $c = new \LazopClient($url, $app_key, $secret_key);
            $request = new \LazopRequest('/logistics/epis/packages');

            $payload = [
                'shipper' => [ 'externalSellerId' => $seller_id ],
                'dimWeight' => [
                    'length' => 10,
                    'width'  => 10,
                    'weight' => 100,
                    'height' => 10
                ],
                'origin' => [
                    "address"=> [
                        "city"=> $store->masterAddress?->city_name,
                        "postcode"=> $store->masterLocation?->postal_code,
                        "details"=> $store->masterLocation?->address,
                        "id"=> $store->masterAddress?->subdistrict_id,
                        "type"=> "home"
                    ],
                    "phone"=> $store->phone,
                    "name"=> $store->name,
                    "email"=> $store->email
                ],
                'destination' => [
                    "address"=> [
                        "city"=> $userAddress->masterAddress?->city_name,
                        "postcode"=> $userAddress->postal_code,
                        "details"=> $userAddress->address_line,
                        "id"=> $userAddress->masterAddress?->subdistrict_id,
                        "type"=> "home"
                    ],
                    "phone"=> $userAddress->phone_number,
                    "name"=> $userAddress->receiver_name
                ],
                'payment' => [
                    "totalAmount"=> $orderTotal,
                    "insuranceAmount"=> $orderTotal,
                    'currency' => 'IDR',
                    'paymentType' => 'NON-COD'
                ],
                'items' => $orderItems,
                'dangerousGood' => 'false',
                'externalOrderId' => $order->order_number,
                'platformOrderCreationTime' => Carbon::parse($order->created_at)->valueOf(),
                'packageType' => 'Sales_order',
                'deliveryOption' => 'standard'
            ];

            foreach ($payload as $key => $value) {
                // Only JSON-encode arrays/objects
                $request->addApiParam($key, is_array($value) || is_object($value) ? json_encode($value) : $value);
            }
            
            $response = json_decode($c->execute($request));

            if($isDebug) return $response;

            LexLog::create([
                'order_id' => $order_id,
                'target' => '/logistics/epis/packages',
                'payload' => $payload,
                'response' => $response
            ]);

            $order->package_code = $response->data->packageCode;
            $order->tracking_number = $response->data->trackingNumber;
            $order->update();                
        }
    }

    public function getShippingFee($snapshot, $newUserAddress = null, $isDebug = false){
        if( !empty( $snapshot['couriers']['pickup_location']['subdistrict_id'] ) ){
            require_once base_path('resources/sdk/Lazop/LazopSdk.php');

            // Snapshot Data
            $from_address_id = $snapshot['couriers']['pickup_location']['subdistrict_id'];
            $to_address_id = ($newUserAddress) ? $newUserAddress->subdistrict_id : $snapshot['user_address']['subdistrict_id'];
            $insurance = $snapshot['order_summary']['insurance_raw'];
            
            // Config
            $seller_id = config('lazop.seller_id');
            $platform_name = config('lazop.platform_name');
            $warehouse_name = config('lazop.warehouse_name');
            $warehouse_code = config('lazop.warehouse_code');

            $url = config('lazop.url');
            $app_key = config('lazop.app_key');
            $secret_key = config('lazop.secret_key');

            $c = new \LazopClient($url, $app_key, $secret_key);
            $request = new \LazopRequest('/logistics/epis/estimate_shipping_fee');

            $payload = [
                'externalSellerId'  => $seller_id,
                'platformName'      => $platform_name,
                'fromAddressId'     => $from_address_id,
                'toAddressId'       => $to_address_id,
                'chargeFactor'      => [
                    'insuranceAmount'   => $insurance,
                    'fulfillmentMethod' => 'Dropshipping',
                    'weight'            => '100',
                    'packageType'       => 'Sales_order',
                    'deliveryOption'    => 'standard',
                    'paymentType'       => 'COD',
                ]
            ];

            foreach ($payload as $key => $value) {
                $request->addApiParam($key, is_array($value) || is_object($value) ? json_encode($value) : $value);
            }
            
            $response = json_decode($c->execute($request));

            // Jika hanya debug, menampilkan response asli dari lazada tanpa modifikasi
            if($isDebug) return $response;

            // Jika tidak debug, modifikasi response dan tambah property insurance
            if(!empty($response->success)){
                $response->custom = (object) [
                    'insurance' => $insurance
                ];
            } else {
                if($response){
                    $response->success = false;
                    $response->errorMessage = "Please check if store subdistrict & user subdistrict is stored in database";
                }
            }

            return $response;
        }
    }

    public function calculateShippingFee($shippingFee)
    {
        // Make sure 'data' exists and is an array
        if (!isset($shippingFee->data) || !is_array($shippingFee->data)) {
            return 0;
        }

        $sum = 0;

        foreach ($shippingFee->data as $item) {
            // Some items may not have 'amount', default to 0
            $amount = isset($item->amount) ? (float) $item->amount : 0;
            $tax = isset($item->taxAmount) ? (float) $item->taxAmount : 0;
            $sum += ($amount + $tax);
        }

        if($shippingFee->custom){
            $custom = $shippingFee->custom;
            $sum += $custom->insurance;
        }

        $fee = ceil($sum);

        // Get delivery detail from database
        $key = "delivery-lazada";
        $courier = collect(CourierData::listsForApi())->firstWhere('key', $key);

        if (!$courier) {
            return null; // or throw new Exception("Courier not found");
        }
        
        return [
            'name' => $courier['name'] ?? 'Unknown',
            'key' => $key,
            'fee_raw' => $fee,
            'fee' => number_format($sum, 0, ',', '.'),
            'is_discount' => false,
            'estimated' => Carbon::now()->addDays(3)->format('Y-m-d')
        ];
    }
}