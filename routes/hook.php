<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Hook\LazadaController;
use Illuminate\Support\Facades\Route;



Route::post('lazada', [LazadaController::class, 'callbackLazada']);