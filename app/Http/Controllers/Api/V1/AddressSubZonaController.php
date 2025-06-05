<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Dump\ExportSubZonesService;
use Illuminate\Http\Request;

class AddressSubZonaController extends Controller
{
  public function exportarSubzonas(Request $request)
{
    $idsUbicacionGeo = $request->input('ids_ubicacion_geo', []);
    $connection = $request->input('connection', 'default');
    $cycle = $request->input('cycle', 1);

    $sqlitePath = storage_path("app/db-dumps/{$connection}/output_audit_cycle_{$cycle}_database.db");

    $exporter = new ExportSubZonesService();
    $exporter->export($idsUbicacionGeo, $sqlitePath);

    return response()->json(['ok' => true, 'path' => $sqlitePath]);
}

}