<?php

use App\Http\Controllers\BotManController;
use Illuminate\Support\Facades\Route;

Route::match(['get', 'post'], '/botman', [BotManController::class, 'handleMainBot']);
Route::match(['get', 'post'], '/botman/{token}', [BotManController::class, 'handleDynamicBot']);