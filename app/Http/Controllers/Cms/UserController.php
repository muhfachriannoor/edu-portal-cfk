<?php

namespace App\Http\Controllers\Cms;

use App\Models\User;
use App\Http\Controllers\Cms\CmsController;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class UserController extends CmsController
{
    /**
     * @var string
     */
    protected $resourceName = 'customer';

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
        return (new User)->getDatatables(); 
    }

    /**
     * Display a listing of the resource (Index Page).
     */
    public function index()
    {
        return view("cms.{$this->resourceName}.index", [
            'resourceName' => $this->resourceName,
            'pageMeta' => [
                'title' => 'User List'
            ]
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $customer)
    {
        return view("cms.{$this->resourceName}.detail", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'customer' => $customer,
            'pageMeta' => [
                'title' => 'Customer Detail',
                'method' => null,
                'url' => null
            ]
        ]);
    }

    public function export(Request $request)
    {
        ini_set('memory_limit', -1);
        ini_set('max_execution_time', -1);

        $attributes = [
            'row_number' => 'No',
            'name' => 'Name',
            'email' => 'Email',
            'mobile_number' => 'Mobile Number',
            'created_at' => 'Date Registered',
            'onboarding_completed' => 'Onboarding Status',
        ];

        (new User)->exportToExcel(
            $attributes,
            function(Builder $builder) {
                return $builder->select([
                    'name', 'email', 'mobile_number', 'created_at', 'onboarding_completed'
                ])->orderBy('created_at', 'DESC');
            },
            function ($collection, $key, $value, $index) {
                if ($key === 'row_number') {
                    return $index + 1;
                }

                if ($key === 'onboarding_completed') {
                    return $collection->onboarding_completed == 1 ? 'Completed' : 'Incomplete';
                }

                if ($key === 'created_at') {
                    return $collection->created_at ? $collection->created_at->toDatetimeString() : '-';
                }

                return $collection->$key;
            }
        );
    }
}