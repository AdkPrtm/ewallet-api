<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OperatorCard;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;

class OperatorCardController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->query('limit') ? $request->query('limit') : 10;

        $operatorCard = OperatorCard::with('dataPlans')
            ->where('status', 'active')
            ->paginate($limit);

        $operatorCard->getCollection()->transform(function ($item) {
            $item->thumbnail =$item->thumbnail ? url($item->thumbnail) : "";
            return $item;
        });
        return ResponseFormatter::success([$operatorCard], '', 200);
    }
}
