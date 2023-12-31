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
use App\Models\TokenDevice;
use NumberFormatter;

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
            return ResponseFormatter::error(message: 'Validation Failed', code: 400);
        }

        $sender = auth()->user();
        $receiver = User::select('users.id', 'users.username', 'users.name')
            ->join('wallets', 'wallets.user_id', 'users.id')
            ->where('users.username', $request->send_to)
            ->orWhere('wallets.card_number', $request->send_to)
            ->first();

        $pinChecker = pinChecker($request->pin);

        if (!$pinChecker) {
            return ResponseFormatter::error(message: 'Your PIN is wrong', code: 400);
        }

        if (!$receiver) {
            return ResponseFormatter::error(message: 'User receiver not found', code: 400);
        }

        if ($sender->id == $receiver->id) {
            return ResponseFormatter::error(message: 'You cant send to yourself', code: 400);
        }

        $senderWallet = Wallet::where('user_id', $sender->id)->first();

        if ($senderWallet->balance < $request->amount) {
            return ResponseFormatter::error(message: 'You balance not enough', code: 400);
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

            $amount = new NumberFormatter("en_US", NumberFormatter::CURRENCY);
            $nominalTransfer = $request->amount;
            $nominalFormatter = $amount->formatCurrency((int)$nominalTransfer, 'IDR');
            $output = preg_replace( '/[^0-9,"."]/', '', $nominalFormatter );

            $tokenDevice = TokenDevice::where('user_id', $receiver->id)->first();
            if ($tokenDevice) {
                sendNotifToUser($tokenDevice->token_device, 'Hei, ' . strtoupper($receiver->name) . ' baru saja kirim uang ke kamu Rp ' . $output, 'Cek selengkapnya disini');
            }

            DB::commit();
            return ResponseFormatter::success($nominalFormatter, 'Transfer Success', 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return ResponseFormatter::error(message: $th->getMessage(), code: 500);
        }
    }
}
