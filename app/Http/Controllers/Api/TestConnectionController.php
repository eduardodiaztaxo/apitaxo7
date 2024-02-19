<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TestConnectionController extends Controller
{
    //
    public function pin(Request $request){
        return response()->json([
            'status'=>'OK', 
            'message' => 'success connection', 
            'data' => [
                'from' => $request->getClientIp(),
                'date' => date('Y-m-d H:i:s')
            ]
        ]);
    }
}
