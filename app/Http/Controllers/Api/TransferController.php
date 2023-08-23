<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\TransactionHistory;
use App\Models\TransactionType;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Helpers\ResponseFormatter;

class TransferController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->only('amount', 'pin', 'send_to');

        $validator = Validator::make($data, [
            'amount' => 'required|integer|min:1000',
            'pin' => 'required|digits:6',
            'send_to' => 'required',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error([], 'Validation Failed', 400);
        }

        $sender = auth()->user();
        $receiver = User::select('users.id', 'users.username')
            ->join('wallets', 'wallets.user_id', 'users.id')
            ->where('users.username', $request->send_to)
            ->orWhere('wallets.card_number', $request->send_to)
            ->first();

        $pinChecker = pinChecker($request->pin);

        if (!$pinChecker) {
            return ResponseFormatter::error([], 'Your PIN is wrong', 400);
        }

        if (!$receiver) {
            return ResponseFormatter::error([], 'User receiver not found', 400);
        }

        if ($sender->id == $receiver->id) {
            return ResponseFormatter::error([], 'You cant send to yourself', 400);
        }

        $senderWallet = Wallet::where('user_id', $sender->id)->first();

        if ($senderWallet->balance < $request->amount) {
            return ResponseFormatter::error([], 'You balance not enough', 400);
        }

        DB::beginTransaction();

        try {
            $transactionType = TransactionType::whereIn('code', ['receive', 'transfer'])->orderBy('code', 'asc')->get();
            $receiveTransactionType = $transactionType->first();
            $transferTransactionType = $transactionType->last();

            $transactionCode = strtoupper(Str::random(10));
            $paymentMethod = PaymentMethod::where('code', 'ewallet')->first();

            Transaction::create([
                'user_id' => $sender->id,
                'transaction_type_id' => $transferTransactionType->id,
                'description' => 'Transfer funds to ' . $receiver->username,
                'amount' => $request->amount,
                'transaction_code' => $transactionCode,
                'status' => 'Success',
                'payment_method_id' => $paymentMethod->id
            ]);

            $senderWallet->decrement('balance', $request->amount);

            Transaction::create([
                'user_id' => $receiver->id,
                'transaction_type_id' => $receiveTransactionType->id,
                'description' => 'Receive funds from ' . $sender->username,
                'amount' => $request->amount,
                'transaction_code' => $transactionCode,
                'status' => 'Success',
                'payment_method_id' => $paymentMethod->id
            ]);

            Wallet::where('user_id', $receiver->id)->increment('balance', $request->amount);

            TransactionHistory::create([
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'transaction_code' => $transactionCode,
            ]);

            DB::commit();
            return ResponseFormatter::success([], 'Transfer Success', 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return ResponseFormatter::error([], $th->getMessage(), 500);
        }
    }
}
