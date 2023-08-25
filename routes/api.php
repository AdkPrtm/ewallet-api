<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DataPlanController;
use App\Http\Controllers\Api\OperatorCardController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\TipController;
use App\Http\Controllers\Api\TopUpController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\TransferController;
use App\Http\Controllers\Api\TransferHistoryController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\WebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::post('webhooks', [WebhookController::class, 'update']);
Route::post('isdataexists', [UserController::class, 'isDataExist']);

Route::group(['middleware' => ['jwt.verify']], function () {
    Route::post('topup', [TopUpController::class, 'store']);

    Route::post('transfer', [TransferController::class, 'store']);
    
    Route::post('dataplans', [DataPlanController::class, 'store']);
    
    Route::get('operatorlist', [OperatorCardController::class, 'index']);
    
    Route::get('paymentmethod', [PaymentMethodController::class, 'index']);
    
    Route::get('transferhistory', [TransferHistoryController::class, 'index']);
    
    Route::get('transactions', [TransactionController::class, 'index']);

    Route::get('users', [UserController::class, 'show']);
    Route::get('users/{username}', [UserController::class, 'getUserByUsername']);
    Route::put('users/', [UserController::class, 'update']);
    Route::post('logout', [UserController::class, 'logout']);

    Route::get('wallet', [WalletController::class, 'show']);
    Route::put('wallet', [WalletController::class, 'update']);

    Route::get('tips', [TipController::class, 'index']);
});
