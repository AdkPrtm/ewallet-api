<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\ResponseFormatter;

class WalletController extends Controller
{
    public function show()
    {
        $user = auth()->user();

        $wallet = Wallet::select('pin', 'balance', 'card_number')->where('user_id', $user->id)->first();

        return ResponseFormatter::success([$wallet], '', 200);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'previous_pin' => 'required|digits:6',
            'new_pin' => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error([], 'Validation Failed', 400);
        }

        if (!pinChecker($request->previous_pin)) {
            return ResponseFormatter::error([], 'Your old pin is wrong', 400);
        }

        $user = auth()->user();

        try {
            Wallet::where('user_id', $user->id)
                ->update(['pin' => $request->new_pin]);

            return ResponseFormatter::success([], 'Pin updated', 200);
        } catch (\Throwable $th) {
            return ResponseFormatter::error([], $th->getMessage(), 500);
        }
    }
}
