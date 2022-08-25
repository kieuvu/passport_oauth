<?php

use App\Http\Controllers\PassportController;
use Illuminate\Support\Facades\Route;

Route::post('api/login', [PassportController::class,'login']);
