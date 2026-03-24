<?php

namespace App\Http\Controllers\Cms;

use App\Models\ChannelCategory;
use App\Http\Requests\PaymentCategoryRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Cms\CmsController;
use App\Models\Gateway;
use Illuminate\Http\JsonResponse;

class PaymentCategoryController extends CmsController
{
    /**
     * @var string
     */
    protected $resourceName = 'payment_category';

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
        return (new ChannelCategory)->getDatatables(); 
    }

    /**
     * Display a listing of the resource (Index Page).
     */
    public function index()
    {
        return view("cms.{$this->resourceName}.index", [
            'resourceName' => $this->resourceName,
            'pageMeta' => [
                'title' => 'Payment Category List'
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(ChannelCategory $paymentCategory)
    {
        return view("cms.{$this->resourceName}.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'paymentCategory' => $paymentCategory,
            'pageMeta' => [
                'title' => 'Create Payment Category',
                'method' => 'post',
                'url' => route('secretgate19.payment_category.store')
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PaymentCategoryRequest $request): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $validateData = $request->validated();
            $gateway = Gateway::where('code', 'XENDIT')->first();

            $validateData['gateway_id'] = $gateway->id;
            $data = ChannelCategory::create($validateData);

            $this->handleImage($data); 
            
            DB::commit();

            return to_route('secretgate19.payment_category.index')
                ->with('success', 'Payment Category created successfully.');

        } catch(\Exception $e){
            DB::rollback();
            report($e);
            throw $e; 
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ChannelCategory $paymentCategory)
    {
        return view("cms.{$this->resourceName}.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'paymentCategory' => $paymentCategory,
            'pageMeta' => [
                'title' => 'View Payment Category',
                'method' => null,
                'url' => null
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ChannelCategory $paymentCategory)
    {
        return view("cms.{$this->resourceName}.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'paymentCategory' => $paymentCategory,
            'pageMeta' => [
                'title' => 'Edit Payment Category',
                'method' => 'PUT',
                'url' => route('secretgate19.payment_category.update', $paymentCategory->id)
            ]
        ]);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PaymentCategoryRequest $request, ChannelCategory $paymentCategory): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $validateData = $request->validated();
            $paymentCategory->update($validateData);

            $this->handleImage($paymentCategory);

            DB::commit();
            
            return to_route('secretgate19.payment_category.index')
                ->with('success', 'Payment Category updated successfully.');
        } catch(\Exception $e){
            DB::rollback();
            report($e);
            throw $e;
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ChannelCategory $paymentCategory): JsonResponse
    {
        $paymentCategory->delete();

        return response()->json(null, 204);
    }

    private function handleImage($paymentCategory)
    {
        $fields = ['icon_image'];
        foreach($fields as $field){
            if(request()->hasFile($field)){
                $file = request()->file($field);

                $paymentCategory->saveFile(
                    $file,
                    'channel_categories/icon',
                    [
                        'field' => $field,
                        'name' => $file->getClientOriginalName()
                    ]
                );
            }
        }        
    }
}