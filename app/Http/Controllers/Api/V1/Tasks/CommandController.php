<?php

namespace App\Http\Controllers\Api\V1\Tasks;

use App\Http\Controllers\Controller;
use App\Jobs\RunCommandJob;
use Illuminate\Http\Request;

class CommandController extends Controller
{
    //
    public function runRelateMarkersToAreasCommand(Request $request)
    {

        $request->validate([
            'level'   => 'required_without:areas_ids|integer',
            'areas_ids' => 'required_without:level|string'
        ]);

        $connection = $request->user()->conn_field;
        $level = $request->level;
        $areas_ids = $request->areas_ids;
        $email = $request->user()->email;

        RunCommandJob::dispatch('command:realte-markers-to-aeras', ['--connection' => $connection, '--level' => $level, '--areas_ids' => $areas_ids], $email)->delay(now()->addSeconds(10));;

        return response()->json(['status' => 'OK', 'message' => 'Se ha iniciado la ejecución de la tarea, esto demorará algunos minutos, un mail será enviado cuando finalice su ejecución']);
    }
}
