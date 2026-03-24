<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Cms\CmsController;
use App\Http\Controllers\Cms\AuthController;
use App\Http\Controllers\Cms\UserController;
use App\Http\Controllers\Cms\AdminController;

Route::prefix('admin')->name('admin.')->middleware('guest:admin')->group(function () {
    Route::get('/login', [AuthController::class, 'index'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate']);
    Route::get('/password-request', [AuthController::class, 'passwordRequest'])->name('password.request');
});

Route::middleware('auth:admin')->group(function () {
    Route::prefix('admin')->name('admin.')->group(function(){
        Route::get('/',  [CmsController::class, 'dashboard'])->name('index');
        Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [CmsController::class, 'dashboard'])->name('dashboard');

        /**
         * -------------------- Section - Main Menu --------------------
         */

        Route::prefix('course')->name('course.')->group(function () {
            Route::get('/datatables', [CourseController::class, 'datatables'])->name('datatables');
            Route::resource('/', CourseController::class)->parameters(['' => 'category']);
        });

        /**
         * Dropdown - Product
         * Page - Category
         */
        Route::prefix('category')->name('category.')->group(function () {
            Route::get('/datatables', [CategoryController::class, 'datatables'])->name('datatables');
            Route::resource('/', CategoryController::class)->parameters(['' => 'category']);
        });

        
    });
});


/**
 * Page
 */
Route::get('/', function(){
    return redirect()->route('admin.dashboard');
});

Route::get('/login', function(){
    return redirect()->route('admin.login');
})->name('login');

// Route::get('/test-email', function () {
//     // Ambil 1 order contoh
//     $order = App\Models\Order::first(); 
    
//     // Simulasikan pemanggilan service
//     $orderService = app(App\Services\OrderService::class);
//     $payloads = $orderService->prepareEmailPayload($order, 'pending');

//     // Return view untuk cek delivery
//     return view($payloads['user']['view'], [
//         'model' => $order,
//         'paymentUrl' => 'https://google.com',
//         'supportUrl' => 'https://google.com'
//     ]);
// });

Route::get('/test-email-waiting', function () {
    // 1. Ambil data order terakhir dari database agar data yang muncul asli
    // Atau jika database kosong, kamu bisa gunakan Order::factory()->create() jika ada
    $order = App\Models\Order::with(['orderItems.productVariant.product', 'store', 'user', 'payment'])->latest()->first();

    // 2. Jika tidak ada data di database, kita buat object kosong agar tidak error (Opsional)
    if (!$order) {
        return "Belum ada data order di database untuk ditest. Silahkan buat satu pesanan dulu.";
    }

    // 3. Siapkan variabel pendukung yang biasanya dikirim lewat Mailalbe
    $data = [
        'model'      => $order,
        'urlPath' => 'https://example.com/pay/' . $order->order_number,
        'invoiceUrl' => 'https://sarinah.co.id/contact',
        'supportUrl' => 'https://sarinah.co.id/contact',
    ];

    // 4. Return view langsung ke browser
    // return view ('emails.users.cancel_delivery', $data);
    // return view ('emails.users.ready_pickup', $data);
    return view ('emails.users.arrived_delivery', $data);
});

/**
 * Ajax URL
 */

Route::prefix('ajax')
    ->name('ajax.')
    ->middleware(['auth:admin,store_owner'])
    ->group(function(){
        Route::get('/category-list', [SubCategoryController::class, 'subCategoryList'])->name('category_list');
});