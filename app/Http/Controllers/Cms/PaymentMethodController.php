<?php

namespace App\Http\Controllers\Cms;

use App\Models\Channel;
use App\Http\Requests\PaymentMethodRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Cms\CmsController;
use App\View\Data\PaymentCategoryData;
use Illuminate\Http\JsonResponse;

class PaymentMethodController extends CmsController
{
    /**
     * @var string
     */
    protected $resourceName = 'payment_method';

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
        return (new Channel)->getDatatables(); 
    }

    /**
     * Get lists.
     */
    public function getLists()
    {
        return [
            'payment_category' => PaymentCategoryData::lists(),
        ];
    }

    /**
     * Display a listing of the resource (Index Page).
     */
    public function index()
    {
        return view("cms.{$this->resourceName}.index", [
            'resourceName' => $this->resourceName,
            'pageMeta' => [
                'title' => 'Payment Method List'
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Channel $paymentMethod)
    {
        return view("cms.{$this->resourceName}.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'lists' => $this->getLists(),
            'paymentMethod' => $paymentMethod,
            'pageMeta' => [
                'title' => 'Create Payment Method',
                'method' => 'post',
                'url' => route('secretgate19.payment_method.store')
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PaymentMethodRequest $request): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $validateData = $request->validated();
            $data = Channel::create($validateData);

            $this->handleImage($data); 
            
            DB::commit();

            return to_route('secretgate19.payment_method.index')
                ->with('success', 'Payment Method created successfully.');

        } catch(\Exception $e){
            DB::rollback();
            report($e);
            throw $e; 
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Channel $paymentMethod)
    {
        return view("cms.{$this->resourceName}.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'lists' => $this->getLists(),
            'paymentMethod' => $paymentMethod,
            'pageMeta' => [
                'title' => 'View Payment Method',
                'method' => null,
                'url' => null
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Channel $paymentMethod)
    {
        return view("cms.{$this->resourceName}.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'lists' => $this->getLists(),
            'paymentMethod' => $paymentMethod,
            'pageMeta' => [
                'title' => 'Edit Payment Method',
                'method' => 'PUT',
                'url' => route('secretgate19.payment_method.update', $paymentMethod->id)
            ]
        ]);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PaymentMethodRequest $request, Channel $paymentMethod): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $validateData = $request->validated();
            $paymentMethod->update($validateData);

            $this->handleImage($paymentMethod);

            DB::commit();
            
            return to_route('secretgate19.payment_method.index')
                ->with('success', 'Payment Method updated successfully.');
        } catch(\Exception $e){
            DB::rollback();
            report($e);
            throw $e;
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Channel $paymentMethod): JsonResponse
    {
        $paymentMethod->delete();

        return response()->json(null, 204);
    }

    private function handleImage($paymentMethod)
    {
        $fields = ['channel_image'];
        foreach($fields as $field){
            if(request()->hasFile($field)){
                $file = request()->file($field);

                $paymentMethod->saveFile(
                    $file,
                    'channels',
                    [
                        'field' => $field,
                        'name' => $file->getClientOriginalName()
                    ]
                );
            }
        }        
    }
}