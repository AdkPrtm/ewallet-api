<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Helpers\ResponseFormatter;

class PaymentMethodController extends Controller
{
    public function index()
    {
        $banks = PaymentMethod::where('status', 'active')
            ->where('code', '!=', 'ewallet')
            ->get()
            ->map(function ($item) {
                $item->thumbnail = $item->thumbnail ? url('banks/' . $item->thumbnail) : '';
                return $item;
            });
        return ResponseFormatter::success([$banks], '', 200);

    }
}
