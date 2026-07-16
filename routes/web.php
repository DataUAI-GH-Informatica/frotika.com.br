<?php

use App\Http\Controllers\Tenancy\RegisterOwnerAndCompanyController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/registrar', RegisterOwnerAndCompanyController::class);
