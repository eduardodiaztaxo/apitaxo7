<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogAppController extends Controller
{
    //
    public function logsApp(Request $request)
    {


        Log::channel('appMobil')->debug($request->message, [
            'tag' => $request->tag,
            'timestamp' => $request->timestamp
        ]);

        return response()->json(['status' => 'OK'], 201);
    }
}
