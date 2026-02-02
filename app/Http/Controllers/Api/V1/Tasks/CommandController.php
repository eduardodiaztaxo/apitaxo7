<?php

namespace App\Http\Controllers\Api\V1\Tasks;

use App\Http\Controllers\Controller;
use App\Jobs\RunCommandJob;
use App\Models\Maps\MapPolygonalArea;
use Illuminate\Http\Request;

class CommandController extends Controller
{
    //
    public function runRelateMarkersToAreasCommand(Request $request)
    {

        $request->validate([
            'level'   => 'required_without:areas_ids|integer',
            'areas_ids' => 'required_without:level|string',
            'email' => 'sometimes|email'
        ]);

        if($request->areas_ids) {

            $notLevel = MapPolygonalArea::whereNotIn('level', [10,11,12])->whereIn('id', explode(',', $request->areas_ids))->exists();
            if ($notLevel) {
                return response()->json(['status' => 'error', 'message' => 'Solo poligonos padre, hijo y nietos es permitido realizar esta acción'], 422);
            }
        }

        $connection = $request->user()->conn_field;
        $level = $request->level;
        $areas_ids = $request->areas_ids;
        $email = $request->email ?? $request->user()->email;

        RunCommandJob::dispatch('command:relate-markers-to-areas', ['--connection' => $connection, '--level' => $level, '--areas_ids' => $areas_ids], $email)->delay(now()->addSeconds(10));;

        return response()->json(['status' => 'OK', 'message' => 'Se ha iniciado la ejecución de la tarea, esto demorará algunos minutos, un mail será enviado a '.$email.' cuando finalice su ejecución']);
    }

    /** 
     * Run the relate inventory markers to areas command.
     * @return \Illuminate\Http\Response
     */
    public function runRelateInventoryMarkersToAreasCommand(Request $request)
    {

        $request->validate([
            'level'   => 'required_without:areas_ids|integer',
            'areas_ids' => 'required_without:level|string',
            'email' => 'sometimes|email'
        ]);

        if($request->areas_ids) {

            $notLevel = MapPolygonalArea::where('level', '!=', 2)->whereIn('id', explode(',', $request->areas_ids))->exists();
            if ($notLevel) {
                return response()->json(['status' => 'error', 'message' => 'Solo areas verdes es permitido realizar esta acción'], 422);
            }
        }

        $connection = $request->user()->conn_field;
        $level = $request->level;
        $areas_ids = $request->areas_ids;
        $email = $request->email ?? $request->user()->email;

        RunCommandJob::dispatch('command:relate-markers-to-areas', ['--connection' => $connection, '--level' => $level, '--areas_ids' => $areas_ids], $email)->delay(now()->addSeconds(10));

        RunCommandJob::dispatch('command:relate-inventory-markers-to-areas', ['--connection' => $connection, '--level' => $level, '--areas_ids' => $areas_ids], $email)->delay(now()->addSeconds(30));

        return response()->json(['status' => 'OK', 'message' => 'Se ha iniciado la ejecución de la tarea (level '.$level.'), esto demorará algunos minutos, dos emails serán enviados a '.$email.' cuando finalice su ejecución']);
    }
}
