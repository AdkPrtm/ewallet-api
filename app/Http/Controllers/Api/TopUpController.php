<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\TransactionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Helpers\ResponseFormatter;
use Illuminate\Support\Str;

class TopUpController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->only('amount', 'pin', 'payment_method_code');

        $validator = Validator::make($data, [
            'amount' => 'required|integer|min:10000',
            'pin' => 'required|digits:6',
            'payment_method_code' => 'required|in:bni_va,bca_va',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(message: 'Validation Failed', code : 400);
            
        }

        $pinChecker = pinChecker($request->pin);

        if (!$pinChecker) {
            return ResponseFormatter::error(message: 'Your pin is wrong', code : 400);
        }

        $transactionType = TransactionType::where('code', 'top_up')->first();

        $paymentMethod = PaymentMethod::where('code', $request->payment_method_code)->first();

        DB::beginTransaction();
        try {
            $transaction = Transaction::create([
                'user_id' => auth()->user()->id,
                'payment_method_id' => $paymentMethod->id,
                'transaction_type_id' => $transactionType->id,
                'amount' => $request->amount,
                'transaction_code' => strtoupper(Str::random(10)),
                'description' => 'Top Up via ' . $paymentMethod->name,
                'status' => 'pending',
            ]);

            //call to midtrans

            $params = $this->buildMidtransParameter([
                'transaction_code' => $transaction->transaction_code,
                'amount' => $transaction->amount,
                'payment_method' => $paymentMethod->code,
            ]);

            $midtrans = $this->callMidtrans($params);

            DB::commit();

            return ResponseFormatter::success($midtrans, '', 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return ResponseFormatter::error(message: $th->getMessage(), code: 500);
        }
    }

    private function callMidtrans(array $params)
    {
        \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        \Midtrans\Config::$isProduction = (bool) env('MIDTRANS_IS_PRODUCTION');
        \Midtrans\Config::$isSanitized = (bool) env('MIDTRANS_IS_SANITIZED');
        \Midtrans\Config::$is3ds = (bool) env('MIDTRANS_IS_3DS');

        $createTransaction = \Midtrans\Snap::CreateTransaction($params);

        return [
            'redirect_url' => $createTransaction->redirect_url,
            'token' => $createTransaction->token,
        ];
    }

    private function buildMidtransParameter(array $params)
    {
        $transactionDetail = [
            'order_id' => $params['transaction_code'],
            'gross_amount' => $params['amount'],
        ];

        $user = auth()->user();
        $splitName = $this->splitName($user->name);
        $customerDetails = [
            'first_name' => $splitName['first_name'],
            'last_name' => $splitName['last_name'],
            'email' => $user->email
        ];

        $enablePayment = [
            $params['payment_method']
        ];

        return [
            'transaction_details' => $transactionDetail,
            'çustomer_details' => $customerDetails,
            'enable_payments' => $enablePayment,
        ];
    }

    private function splitName($fullname)
    {
        $name = explode(' ', $fullname);
        $lastName = count($name) > 1 ? array_pop($name) : $fullname;
        $firsName = implode(' ', $name);

        return [
            'first_name' => $firsName,
            'last_name' => $lastName
        ];
    }
}
