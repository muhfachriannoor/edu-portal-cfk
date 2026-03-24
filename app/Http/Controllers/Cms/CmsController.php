<?php

namespace App\Http\Controllers\Cms;

use App\Services\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;

class CmsController extends BaseController
{
    use AuthorizesRequests;

    public function dashboard()
    {
        return view('dashboard.index', [
            'pageMeta' => [
                'title' => 'Dashboard'
            ],
        ]);
    }
}
