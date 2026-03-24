<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Hook\LazadaController;
use App\Http\Controllers\Api\ChannelsController;
use App\Http\Controllers\Api\NewsroomController;
use App\Http\Controllers\Api\LazadaApiController;
use App\Http\Controllers\Api\SubscribeController;
use App\Http\Controllers\Api\MasterDataController;
use App\Http\Controllers\Api\MaintenanceController;
use App\Http\Controllers\Api\ShoppingBagController;
use App\Http\Controllers\Api\UserAddressController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\UserInterestController;
use App\Http\Controllers\Api\UserOnboardingController;


// Route::middleware(['api.maintenance'])->group(function () {

    /**
     * Maintenance Route
     * 
     * for change status
     */
    Route::post('/admin/maintenance', [MaintenanceController::class, 'set']);

    /**
     * Auth Route
     */
    Route::prefix('auth')->middleware(['set.locale'])->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('register', [AuthController::class, 'register']);
        Route::post('resend-otp/{identifier}', [AuthController::class, 'resend']);
        Route::post('verify/{identifier}', [AuthController::class, 'verify']);
        Route::post('tnc', [AuthController::class, 'tnc']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('set-password', [AuthController::class, 'setPassword']);
        Route::post('check-reset-token', [AuthController::class, 'checkResetToken']);
        Route::get('reset-password', [AuthController::class, 'resetPassword'])->name('api.password.reset');
        Route::post('reset-password', [AuthController::class, 'reset'])->name('api.password.update');
        Route::post('refresh-token', [AuthController::class, 'refreshToken']);

        Route::post('firebase-login', [AuthController::class, 'firebaseLogin']);
    });

    Route::middleware(['auth.jwt','set.locale'])->group(function () {

        // User
        Route::prefix('user')->group(function () {

            // Interest User
            Route::get('interests', [UserInterestController::class, 'index']);
            Route::post('interests', [UserInterestController::class, 'updateInterests']);

            // Address User
            Route::apiResource('addresses', UserAddressController::class)->except(['show']);
            Route::post('addresses/{address}/default', [UserAddressController::class, 'setDefaultAddress'])->name('user.addresses.set_default');

            // Profile User
            Route::get('profile', [UserProfileController::class, 'show']);
            Route::put('profile', [UserProfileController::class, 'update']);
            Route::put('change-password', [UserProfileController::class, 'change_password']);
            Route::post('upload-profile', [UserProfileController::class, 'upload_profile']);
            Route::get('email-preference', [UserProfileController::class, 'email_preference']);
            Route::put('email-preference', [UserProfileController::class, 'update_email_preference']);

            // Onboarding User
            Route::post('onboarding', [UserOnboardingController::class, 'onBoardingUser']);
        });

        // Shopping Bag Item
        Route::prefix('shopping-bag')->group(function () {
            Route::get('/', [ShoppingBagController::class, 'getShoppingBag']); 
        
            // CREATE (Add/Increment)
            Route::post('/', [ShoppingBagController::class, 'store']); 

            // Voucher Code (apply and delete)
            Route::post('/apply-voucher', [ShoppingBagController::class, 'applyVoucher']);
            Route::delete('/apply-voucher', [ShoppingBagController::class, 'removeVoucher']);

            // Delivery Method (apply and delete)
            Route::post('/apply-delivery', [ShoppingBagController::class, 'applyDelivery']);

            // Change Address
            Route::patch('/update-address', [ShoppingBagController::class, 'updateUserAddress']);

            // Toggle for SELECT ALL OR UNCHECKED ALL
            Route::patch('/toggle-selected', [ShoppingBagController::class, 'bulkToggleSelection']);

            // Toggle is_selected status true/false (per item)
            Route::patch('/{id}/toggle-selected', [ShoppingBagController::class, 'toggleSelection'])
                ->whereNumber('id');
            
            // UPDATE (Set specific quantity)
            Route::put('/{variant_id}', [ShoppingBagController::class, 'update'])
                ->whereNumber('variant_id');
            
            // DELETE (Remove item)
            Route::delete('/{variant_id}', [ShoppingBagController::class, 'destroy'])
                ->whereNumber(('variant_id'));

            // Change Type `delivery` or `pickup`
            Route::post('/switch-type', [ShoppingBagController::class, 'switchType']);

            // Checkout
            Route::get('/checkout', [ShoppingBagController::class, 'checkout'])->name('api.shopping_bag.checkout');

        });

        // Notify Me
        Route::post('notify-me', [ProductController::class, 'notify_me']);

        // Recommendation Product
        Route::get('recommendation-product', [ProductController::class, 'recommendation'])->middleware(['set.locale']);

        // Notification
        Route::prefix('notification')->group(function () {
            Route::get('list', [NotificationController::class, 'list']);
            Route::get('mark-read/all', [NotificationController::class, 'all']);
            Route::get('read/{uuid}', [NotificationController::class, 'read']);
        });
    });

    Route::prefix('master')
        ->name('api.master.')
        ->middleware(['set.locale'])
        ->group(function() {
            
        // Data Category Only
        Route::get('categories', [MasterDataController::class, 'indexCategories']);
        Route::get('categories/{slug}', [MasterDataController::class, 'showCategoryDetail']);

        // Data SubCategory Only (with filter opsional)
        Route::get('sub-categories', [MasterDataController::class, 'indexSubCategories']);

        // Data Category With SubCategory
        Route::get('categories-with-subs', [MasterDataController::class, 'categoriesWithSubCategories']);

        // Data SubCategory for Homepage Featured (6 data random)
        Route::get('sub-categories/homepage-featured', [MasterDataController::class, 'homepageSubCategories']);

        // Data Courier
        Route::get('couriers', [MasterDataController::class, 'indexCouriers']);

        // Data Voucher
        Route::get('vouchers/', [MasterDataController::class, 'indexVouchers']);

        // Data Store
        Route::get('stores/', [MasterDataController::class, 'stores']);

        // Data Brand
        Route::get('brands/', [MasterDataController::class, 'brands']);

        // Data Indo Region
        Route::get('master-address', [MasterDataController::class, 'master_address'])->name('master_address');
    });

    Route::prefix('generals')->group(function() {
        Route::get('/channels', [ChannelsController::class, 'getChannels']);
        Route::get('/channels/grouped', [ChannelsController::class, 'getChannelsGroupedByCategory']);
    });

    Route::prefix('newsrooms')->middleware(['set.locale'])->group(function() {
        // 1. Combined List & Featured API
        // GET /api/newsrooms (Paginated List)
        // GET /api/newsrooms?featured=true&limit=4 (Featured List)
        Route::get('/', [NewsroomController::class, 'index']);

        // 2. Detail API
        // GET /api/newsrooms/{slug}
        Route::get('/{slug}', [NewsroomController::class, 'show'])->middleware('api.maintenance');
    });

    Route::prefix('orders')->middleware(['auth.jwt','set.locale'])->group(function() {
        // Order
        Route::post('/', [OrderController::class, 'store']);
        Route::post('/{order_id}/cancel', [OrderController::class, 'cancelOrder']);
        Route::post('/{order_id}/change-payment', [OrderController::class, 'changePaymentMethod']);
        Route::post('/{order_id}/confirm-transfer', [OrderController::class, 'confirmTransfer']);

        Route::get('/list', [OrderController::class, 'list']);
        Route::get('/detail/{order_id}', [OrderController::class, 'detail']);

        // Unified Invoice endpoint (HTML or PDF)
        Route::get('/{order_id}/invoice', [OrderController::class, 'invoice']);

        Route::post('/{order_id}/shipment', [OrderController::class, 'shipment']);
        // Route::post('/{order_id}/shipping-fee', [OrderController::class, 'shippingFee']);

        // Buy Again API
        Route::post('/{order_id}/buy-again', [OrderController::class, 'buyAgain']);

        // Delivery List
        Route::post('delivery-list', [OrderController::class, 'deliveryList']);
        // Route::post('shipment', [OrderController::class, 'shipment']);
    });

    Route::prefix('payments')->middleware(['set.locale'])->group(function() {
        // Order or Checkout
        Route::post('/simulate/va', [ChannelsController::class, 'simulateVirtualAccount']);
        Route::post('/simulate/qris', [ChannelsController::class, 'simulateQrPayment']);
    });

    // Product List
    Route::get('featured-product', [ProductController::class, 'featured'])->middleware(['set.locale']);
    Route::get('search-product', [ProductController::class, 'search'])->middleware(['set.locale']);
    Route::get('search-product-result', [ProductController::class, 'result'])->middleware(['set.locale']);
    Route::get('bestseller-product', [ProductController::class, 'bestseller'])->middleware(['set.locale']);
    Route::get('detail-product/{slug}', [ProductController::class, 'detail'])->middleware(['set.locale']);
    Route::get('product-category/{slug}', [ProductController::class, 'category'])->middleware(['set.locale']);

    Route::post('payment/callback', [ChannelsController::class, 'callbackPayment']);

    // Setting
    Route::get('setting', [SettingController::class, 'setting'])->middleware(['set.locale']);

    // Subscribe
    Route::post('subscribe', [SubscribeController::class, 'subscribe']);
// });

Route::prefix('lazada')->middleware(['auth.jwt'])->group(function() {
    Route::get('fee', [LazadaApiController::class, 'fee']);
    Route::get('create', [LazadaApiController::class, 'create']);
});

Route::get('/orders/public/{order_id}/invoice', [OrderController::class, 'invoice'])
    ->name('api.order.invoice.public')
    ->middleware('signed');
// Route::post('webhooks/shipping', [LazadaController::class, 'handle']);