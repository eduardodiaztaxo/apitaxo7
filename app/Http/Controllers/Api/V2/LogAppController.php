<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\Api\V2\StoreAppLogRequest;
use App\Models\AppLog;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class LogAppController extends Controller
{
    public function logsApp(StoreAppLogRequest $request)
    {
        $data = $request->validated();
        $user = $request->user('sanctum');

        if ($user && !empty($user->conn_field) && array_key_exists($user->conn_field, config('database.connections', []))) {
            DB::setDefaultConnection($user->conn_field);
        }

        $appLog = AppLog::create([
            'user_id' => $user ? $user->id : null,
            'event_type' => $data['event_type'],
            'severity' => $data['severity'],
            'message' => $data['message'],
            'metadata' => $data['metadata'] ?? null,
            'client_at' => $data['client_at'] ?? null,
            'platform' => $data['platform'] ?? null,
            'app_version' => $data['app_version'] ?? null,
            'device_id' => $data['device_id'] ?? null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'status' => 'OK',
            'app_log_id' => $appLog->id,
        ], 201);
    }
}
