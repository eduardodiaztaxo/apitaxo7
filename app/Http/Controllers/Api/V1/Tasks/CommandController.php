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
            'level'   => 'required|integer'
        ]);

        $connection = $request->user()->conn_field;
        $level = $request->level;
        $email = $request->user()->email;

        RunCommandJob::dispatch('command:realte-markers-to-aeras', ['--connection' => $connection, '--level' => $level], $email)->delay(now()->addMinutes(1));;

        return response()->json(['status' => 'OK', 'message' => 'Se ha iniciado la ejecución de la tarea, esto demorará algunos minutos, un mail será enviado cuando finalice su ejecución']);
    }
}
