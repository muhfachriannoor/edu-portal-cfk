<?php

namespace App\Http\Controllers\StoreCms;

use App\Models\Store;
use App\Models\Answer;
use App\Models\Client;
use App\Services\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;

class CmsController extends BaseController
{
    use AuthorizesRequests;

    protected $store;

    // public function __construct()
    // {
    //     $this->authorizeResourceWildcard($this->resourceName);
        
    //     $this->middleware(function ($request, $next) {
    //         $slug = $request->route('store');
    //         if ($slug) {
    //             $this->store = Store::where('slug', $slug)->firstOrFail();
    //         }
    //         return $next($request);
    //     });
    // }

    public function dashboard()
    {
        return view('dashboard.index', [
            'pageMeta' => [
                'title' => 'Dashboard'
            ],
        ]);
    }
}
