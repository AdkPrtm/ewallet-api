<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tip;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;

class TipController extends Controller
{
    public function index(Request $request) {
        $limit = $request->query('limit') ? $request->query('limit') : 10;
        
        $tips = Tip::select('id','title', 'url', 'thumbnail')->paginate($limit);

        $tips->getCollection()->transform(function ($item) {
            $item->thumbnail = $item->thumbnail ? url('tips/'.$item->thumbnail) : '';
            return $item;
        });

        return ResponseFormatter::success($tips, '', 200);
    }
}
