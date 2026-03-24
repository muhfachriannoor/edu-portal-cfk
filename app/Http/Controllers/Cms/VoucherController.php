<?php

namespace App\Http\Controllers\Cms;

use Carbon\Carbon;
use App\Models\Voucher;
use Illuminate\Http\Request;
use App\Http\Requests\VoucherRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Cms\CmsController;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

class VoucherController extends CmsController
{
    /**
     * @var string
     */
    protected $resourceName = 'coupon';

    /**
     * Constructor: Authorize resource wildcard.
     */
    public function __construct()
    {
        $this->authorizeResourceWildcard($this->resourceName);
    }

    /**
     * Display a listing of the resource for Datatables.
     */
    public function datatables()
    {
        return (new Voucher)->getDatatables(); 
    }

    /**
     * Display the voucher datatable.
     */
    public function datatablesVoucher($voucherId)
    {
        return (new Voucher)->getVoucherOrdersDatatables($voucherId);
    }

    /**
     * Get lists.
     */
    public function getLists()
    {
        return [
            'types' => [
                'percentage' => 'Percentage (%)',
                'fixed_amount' => 'Amount',
            ]
        ];
    }

    /**
     * Display a listing of the resource (Index Page).
     */
    public function index()
    {
        return view("cms.{$this->resourceName}.index", [
            'resourceName' => $this->resourceName,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'Coupon List'
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Voucher $coupon)
    {
        return view("cms.{$this->resourceName}.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'coupon' => $coupon,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'Create Coupon',
                'method' => 'post',
                'url' => route('secretgate19.coupon.store')
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(VoucherRequest $request)
    {
        DB::beginTransaction();

        try {
            // 1. Create
            $voucher = Voucher::create([
                'voucher_name' => $request->input('voucher_name_en'),
                'voucher_code' => $request->input('voucher_code'),
                'type' => $request->input('type'),
                'amount' => $request->input('amount'),
                'usage_limit' => $request->input('usage_limit'),
                'used_count' => 0,
                'min_transaction_amount' => $request->input('min_transaction_amount'),
                'max_discount_amount' => $request->input('max_discount_amount'),
                'start_date' => Carbon::createFromFormat('Y-m-d H:i', $request->input('start_date')),
                'end_date' => Carbon::createFromFormat('Y-m-d H:i', $request->input('end_date')),
                'is_active' => $request->input('is_active'),
            ]);

            // 2. Handle Image
            $this->handleImage($voucher); 

            // 3. Handle Translations (Title & Content)
            foreach (['en', 'id'] as $locale) {
                $voucher->translations()->create([
                    'locale' => $locale,
                    'name' => $request->input("voucher_name_{$locale}")
                ]);
            }
            
            DB::commit();

            return to_route('secretgate19.coupon.index')
                ->with('success', 'Coupon created successfully.');

        } catch(\Exception $e){
            DB::rollback();
            report($e);
            throw $e; 
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Voucher $coupon)
    {
        $coupon->load('translations');

        $totalOrders = Order::where('status', 5) // Status "completed"
            ->count();

        $couponUsage = Order::where('voucher_id', $coupon->id)
            ->where('status', 5) // Status "completed"
            ->count();

        return view("cms.{$this->resourceName}.detail", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'coupon' => $coupon,
            'totalOrders' => $totalOrders,
            'couponUsage' => $couponUsage,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'View Coupon',
                'method' => null,
                'url' => null
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Voucher $coupon)
    {
        // Load translations agar data terjemahan ada di form
        $coupon->load('translations'); 

        return view("cms.{$this->resourceName}.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'coupon' => $coupon,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'Edit Coupon',
                'method' => 'PUT',
                'url' => route('secretgate19.coupon.update', $coupon->id)
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(VoucherRequest $request, Voucher $coupon): RedirectResponse
    {
        DB::beginTransaction();

        try {
            // 1. Update data
            $coupon->update([
                'voucher_name' => $request->input('voucher_name_en'),
                'voucher_code' => $request->input('voucher_code'),
                'type' => $request->input('type'),
                'amount' => $request->input('amount'),
                'usage_limit' => $request->input('usage_limit'),
                'min_transaction_amount' => $request->input('min_transaction_amount'),
                'max_discount_amount' => $request->input('max_discount_amount'),
                'start_date' => Carbon::createFromFormat('Y-m-d H:i', $request->input('start_date')),
                'end_date' => Carbon::createFromFormat('Y-m-d H:i', $request->input('end_date')),
                'is_active' => $request->input('is_active'),

            ]);
            
            // 2. Handle Image
            $this->handleImage($coupon);

            // 3. Handle Translations (UpdateOrCreate)
            foreach (['en', 'id'] as $locale) {
                $coupon->translations()->updateOrCreate([
                    'locale' => $locale
                ],[
                    ['name'   => $request->input("voucher_name_{$locale}")]
                ]);
            }

            DB::commit();
            
            return to_route('secretgate19.coupon.index')
                ->with('success', 'Coupon updated successfully.');
        } catch(\Exception $e){
            DB::rollback();
            report($e);
            throw $e;
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Voucher $coupon): JsonResponse
    {
        $coupon->translations()->delete();
        $coupon->delete();

        return response()->json(null, 204);
    }

    /**
     * Handle Image.
     */
    private function handleImage($voucher)
    {
        $fields = ['image'];
        foreach($fields as $field){
            if(request()->hasFile($field)){
                $file = request()->file($field);

                $voucher->saveFile(
                    $file,
                    'voucher',
                    [
                        'field' => $field,
                        'name' => $file->getClientOriginalName()
                    ]
                );
            }
        }        
    }
}