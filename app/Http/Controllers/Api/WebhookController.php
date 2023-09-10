<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TokenDevice;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use NumberFormatter;

class WebhookController extends Controller
{
    public function update()
    {
        \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        \Midtrans\Config::$isProduction = (bool) env('MIDTRANS_IS_PRODUCTION');
        $notif = new \Midtrans\Notification();

        $amount = new NumberFormatter("en_US", NumberFormatter::CURRENCY);
        $nominalTopup = $notif->gross_amount;
        $nominalFormatter = $amount->formatCurrency((int)$nominalTopup, 'IDR');
        $output = preg_replace( '/[^0-9,"."]/', '', $nominalFormatter );

        $transactionStatus = $notif->transaction_status;
        $type = $notif->payment_type;
        $transactionCode =  $notif->order_id;
        $fraudStatus = $notif->fraud_status;

        DB::beginTransaction();
        try {
            $status = null;

            if ($transactionStatus == 'capture') {
                if ($fraudStatus == 'accept') {
                    // TODO set transaction status on your database to 'success'
                    // and response with 200 OK
                    $status = 'Sukses';
                }
            } else if ($transactionStatus == 'settlement') {
                // TODO set transaction status on your database to 'success'
                // and response with 200 OK
                $status = 'Sukses';
            } else if (
                $transactionStatus == 'cancel' ||
                $transactionStatus == 'deny' ||
                $transactionStatus == 'expire'
            ) {
                // TODO set transaction status on your database to 'failure'
                // and response with 200 OK
                $status = 'failed';
            } else if ($transactionStatus == 'pending') {
                // TODO set transaction status on your database to 'pending' / waiting payment
                // and response with 200 OK
                $status = 'pending';
            }

            $transaction = Transaction::where('transaction_code', $transactionCode)->first();
            if ($transaction->status != 'success') {
                $transactionAmount = $transaction->amount;
                $userId = $transaction->user_id;

                $transaction->update(['status' => $status]);

                if ($status == 'Sukses') {
                    Wallet::where('user_id', $userId)->increment('balance', $transactionAmount);
                    $tokenDevice = TokenDevice::where('user_id', $userId)->first();
                    if ($tokenDevice) {
                        sendNotifToUser($tokenDevice->token_device, 'Berhasil top up saldo Rp ' . $output, 'Cek selengkapnya disini');
                    }
                }
            }

            DB::commit();
            return response()->json();
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['messages' => $th->getMessage()], 500);
        }
    }
}
