<?php

use Kieuvu\PassportOauth\Controllers\PassportController;
use Illuminate\Support\Facades\Route;

Route::post('api/login', [PassportController::class, 'login']);
Route::post('api/register', [PassportController::class, 'register']);
Route::post('api/refresh', [PassportController::class, 'refreshToken']);
