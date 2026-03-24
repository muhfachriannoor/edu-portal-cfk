<?php

return [
    'seller_id' => env('LAZOP_SELLER_ID', ''),
    'platform_name' => env('LAZOP_PLATFORM_NAME', ''),
    'warehouse_name' => env('LAZOP_WAREHOUSE_NAME', ''),
    'warehouse_code' => env('LAZOP_WAREHOUSE_CODE', ''),
    
    'url' => env('LAZOP_URL', 'https://api.lazada.co.id/rest'),
    'app_key' => env('LAZOP_APP_KEY', ''),
    'secret_key' => env('LAZOP_SECRET_KEY', ''),

    'insurance' => env('LAZOP_INSURANCE')
];