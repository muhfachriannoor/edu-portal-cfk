<?php

namespace App\Http\Controllers\Api;

use App\Models\Store;
use App\Models\Product;
use App\Models\Category;
use App\Models\NotifyMe;
use App\Services\LazadaApi;
use App\View\Data\BrandData;
use Illuminate\Http\Request;
use App\Models\ProductOption;
use Illuminate\Http\Response;
use App\Models\ProductVariant;
use App\View\Data\ProductData;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use App\Models\ProductOptionValue;
use App\Http\Controllers\Controller;
use App\Services\ShoppingBagService;
use App\Http\Requests\UserAddressRequest;
use Illuminate\Pagination\LengthAwarePaginator;

class LazadaApiController extends Controller
{
    use LazadaApi;
    
    private $shoppingBagService;

    public function __construct(ShoppingBagService $shoppingBagService)
    {
        $this->shoppingBagService = $shoppingBagService;
    }

    public function fee()
    {
        $userId = auth()->id();
        $locale = app()->getLocale();
        
        $snapshot = $this->shoppingBagService->buildShoppingBagSnapshotForUser($userId, $locale);

        return $this->getShippingFee($snapshot, null, true);
    }

    public function create()
    {
        $orderId = request()->get('order_id');

        if(!$orderId) return "Order Id is required";

        $transaction = $this->createPackage($orderId, true);

        return response()->json($transaction);
    }
}
