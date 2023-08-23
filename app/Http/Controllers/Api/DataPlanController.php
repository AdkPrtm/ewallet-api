<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\DataPlan;
use App\Models\DataPlanHistory;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\TransactionType;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DataPlanController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data_plan_id' => 'required|integer',
            'phone_number' => 'required|string',
            'pin' => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error([], 'Validation Failed', 400);

        }

        $userId = auth()->user()->id;

        $transactionType = TransactionType::where('code', 'internet')->first();

        $paymentMethod = PaymentMethod::where('code', 'ewallet')->first();

        $userWallet = Wallet::where('user_id', $userId)->first();

        $dataPlan = DataPlan::find($request->data_plan_id);

        if (!$dataPlan) {
            return ResponseFormatter::error([], 'Data plan not found', 400);
        }

        $pinChecker = pinChecker($request->pin);

        if (!$pinChecker) {
            return ResponseFormatter::error([], 'Your pin is wrong', 400);
        }

        if ($userWallet->balance < $dataPlan->price) {
            return ResponseFormatter::error([], 'Your balance is not enough', 400);
        }
        
        DB::beginTransaction();
        try {
            $transaction = Transaction::create([
                'user_id' => $userId,
                'transaction_type_id' => $transactionType->id,
                'payment_method_id' => $paymentMethod->id,
                'amount' => $dataPlan->price,
                'transaction_code' => strtoupper(Str::random(10)),
                'description' => 'Internet Data Plan ' . $dataPlan->name,
                'status' => 'Success',
            ]);

            DataPlanHistory::create([
                'data_plan_id' => $request->data_plan_id,
                'transaction_id' => $transaction->id,
                'phone_number' => $request->phone_number,
            ]);

            $userWallet->decrement('balance', $dataPlan->price);
            DB::commit();

            return ResponseFormatter::success([], 'Buy data plan success', 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return ResponseFormatter::error([], $th->getMessage(), 500);
        }
    }
}
