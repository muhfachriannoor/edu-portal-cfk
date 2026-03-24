<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Client\Request;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard.index');
    }
}
