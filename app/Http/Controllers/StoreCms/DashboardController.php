<?php

namespace App\Http\Controllers\StoreCms;

use Carbon\Carbon;
use App\Models\Store;
use Illuminate\Http\Client\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Cms\CmsController;

class DashboardController extends CmsController
{
    public function index(Store $store)
    {
        return view("store_cms.dashboard.index", [
            'mode' => __FUNCTION__,
            'pageMeta' => [
                'title' => "Dashboard",
                'method' => null,
                'url' => null
            ]
        ]);
    }
}
