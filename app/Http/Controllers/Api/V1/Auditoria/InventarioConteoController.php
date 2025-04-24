<?php

namespace App\Http\Controllers\Api\V1\Auditoria;

use App\Http\Controllers\Controller;
use App\Models\Emplazamiento;
use App\Models\InvCicloPunto;
use App\Models\UbicacionGeografica;
use App\Models\ZonaPunto;
use App\Services\ActivoService;
use App\Services\AuditLabelsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InventarioConteoController extends Controller
{
    //
    public function processConteo(Request $request)
    {


        if ($request->items) {
            $request->merge(['items' => json_encode($request->items)]);
        }


        $request->validate([
            'items'   => 'required|json',
            'idCiclo' => 'required|integer|exists:inv_ciclos,idCiclo',
            'idPunto' => 'required|integer|exists:ubicaciones_geograficas,idUbicacionGeo',
        ]);


        $cicloPunto = InvCicloPunto::where('idCiclo', $request->idCiclo)->where('idPunto', $request->idPunto)->first();

        if (!$cicloPunto) {
            return request()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'La dirección no está asociada al ciclo'
            ], 404);
        }


        $items = json_decode($request->items);

        $assets = [];
        $errors = [];

        foreach ($items as $key => $item) {

            if (isset($item->adicionales)) {
                $item->adicionales = json_encode($item->adicionales);
            }

            $validator = Validator::make((array)$item, $this->rules());

            if ($validator->fails()) {

                $errors[] = ['index' => $key, 'errors' => $validator->errors()->get("*")];
            } else if (empty($errors)) {

                $activo = [
                    'idCiclo'               => $request->idCiclo,
                    'idPunto'               => $request->idPunto,
                    'etiqueta'              => $item->etiqueta,
                    'organica1'             => '0',
                    'organica2'             => '0',
                    'organica3'             => '0',
                    'descripcionUsuario'    => $request->user()->name,
                    // 'created_at'        => date('Y-m-d H:i:s'),
                    // 'updated_at'        => date('Y-m-d H:i:s'),
                ];

                $assets[] = $activo;
            }
        }


        DB::table('inv_conteo_det')->insert($assets);


        return response()->json([
            'status' => 'OK',
            'code'   => 200,
            'message' => 'successfully processed'
        ]);
    }

    /**
     * Process count by Emplazamiento
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function processConteoByEmplazamiento(Request $request)
    {




        if ($request->items) {
            $request->merge(['items' => json_encode($request->items)]);
        }


        $request->validate([
            'items'             => 'required|json',
            'ciclo_id'          => 'required|integer|exists:inv_ciclos,idCiclo',
            'emplazamiento_id'  => 'required|integer|exists:ubicaciones_n2,idUbicacionN2',
        ]);


        $empObj = Emplazamiento::find($request->emplazamiento_id);

        $zonaObj = $empObj->zonaPunto;

        $cicloPunto = InvCicloPunto::where('idCiclo', $request->ciclo_id)->where('idPunto', $zonaObj->idAgenda)->first();

        if (!$cicloPunto) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'La dirección ' . $zonaObj->idAgenda . ' no está asociada al ciclo '
            ], 404);
        }


        $items = json_decode($request->items);

        $cicloObj = $cicloPunto->ciclo;

        $etiquetas = ActivoService::getLabelsByCycleAndEmplazamiento($empObj, $cicloObj);



        $assets = [];
        $errors = [];

        $items = collect($items)->unique();



        $auditLabelServ = new AuditLabelsService($items->pluck('etiqueta')->toArray(), $etiquetas->toArray());



        foreach ($auditLabelServ->getAuditListDetail() as $key => $item) {



            $validator = Validator::make((array)$item, $this->rules());

            if ($validator->fails()) {

                $errors[] = ['index' => $key, 'errors' => $validator->errors()->get("*")];
            } else if (empty($errors)) {

                $activo = [
                    'ciclo_id'          => $request->ciclo_id,
                    'punto_id'          => $zonaObj->idAgenda,
                    'etiqueta'          => $item['etiqueta'],
                    'audit_status'      => $item['audit_status'],
                    'cod_zona'          => $zonaObj->codigoUbicacion,
                    'cod_emplazamiento' => $empObj->codigoUbicacion,
                    'user_id'           => $request->user()->id,
                    'created_at'        => date('Y-m-d H:i:s'),
                    'updated_at'        => date('Y-m-d H:i:s'),
                ];

                $assets[] = $activo;
            }
        }

        //quedan los anteriores obsoletos
        DB::update(
            'UPDATE inv_conteo_registro 
                    SET status = 2, updated_at = NOW() 
                    WHERE ciclo_id = ? AND punto_id = ? AND cod_emplazamiento = ? ',
            [$request->ciclo_id, $zonaObj->idAgenda, $empObj->codigoUbicacion]
        );

        //se insertan los nuevos registros
        DB::table('inv_conteo_registro')->insert($assets);


        return response()->json([
            'status' => 'OK',
            'code'   => 200,
            'message' => 'successfully processed'
        ]);
    }



    /**
     * Process count by Emplazamiento multiple users
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function processConteoByEmplazamientoMultipleUsers(Request $request)
    {


        if ($request->items) {
            $request->merge(['items' => json_encode($request->items)]);
        }


        $request->validate([
            'items'             => 'required|json',
            'ciclo_id'          => 'required|integer|exists:inv_ciclos,idCiclo',
            'emplazamiento_id'  => 'required|integer|exists:ubicaciones_n2,idUbicacionN2',
        ]);


        $empObj = Emplazamiento::find($request->emplazamiento_id);

        $zonaObj = $empObj->zonaPunto;

        $cicloPunto = InvCicloPunto::where('idCiclo', $request->ciclo_id)->where('idPunto', $zonaObj->idAgenda)->first();

        if (!$cicloPunto) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'La dirección ' . $zonaObj->idAgenda . ' no está asociada al ciclo '
            ], 404);
        }


        $items = json_decode($request->items);

        $cicloObj = $cicloPunto->ciclo;

        $etiquetas = ActivoService::getLabelsByCycleAndEmplazamiento($empObj, $cicloObj);


        $items = collect($items)->unique();


        $processedLabels = DB::table("inv_conteo_registro")
            ->where('status', '=', 1)
            ->where('ciclo_id', '=', $request->ciclo_id)
            ->where('punto_id', '=', $zonaObj->idAgenda)
            ->where('cod_zona', '=', $zonaObj->codigoUbicacion)
            ->where('cod_emplazamiento', '=', $empObj->codigoUbicacion)
            ->get();



        $auditLabelServ = new AuditLabelsService($items->pluck('etiqueta')->toArray(), $etiquetas->toArray(), $processedLabels);

        $result = $auditLabelServ->processAuditedLabels_Emplazamiento($request->ciclo_id, $zonaObj->idAgenda, $zonaObj->codigoUbicacion, $empObj->codigoUbicacion, $request->user()->id);


        if (!empty($result['errors'])) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'errors' => $result['errors'],
                'message' => 'Error en etiquetas '
            ], 422);
        }

        return response()->json([
            'status' => 'OK',
            'code'   => 200,
            'message' => 'successfully processed'
        ]);
    }



    /**
     * Process count by Zona
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function processConteoByZona(Request $request)
    {




        if ($request->items) {
            $request->merge(['items' => json_encode($request->items)]);
        }


        $request->validate([
            'items'             => 'required|json',
            'ciclo_id'          => 'required|integer|exists:inv_ciclos,idCiclo',
            'zona_id'           => 'required|integer|exists:ubicaciones_n1,idUbicacionN1',
        ]);


        $zonaObj = ZonaPunto::find($request->zona_id);



        $cicloPunto = InvCicloPunto::where('idCiclo', $request->ciclo_id)->where('idPunto', $zonaObj->idAgenda)->first();

        if (!$cicloPunto) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'La dirección ' . $zonaObj->idAgenda . ' no está asociada al ciclo '
            ], 404);
        }


        $items = json_decode($request->items);

        $cicloObj = $cicloPunto->ciclo;

        $etiquetas = ActivoService::getLabelsByCycleAndZone($zonaObj, $cicloObj);



        $assets = [];
        $errors = [];

        $items = collect($items)->unique();



        $auditLabelServ = new AuditLabelsService($items->pluck('etiqueta')->toArray(), $etiquetas->toArray());



        foreach ($auditLabelServ->getAuditListDetail() as $key => $item) {



            $validator = Validator::make((array)$item, $this->rules());

            if ($validator->fails()) {

                $errors[] = ['index' => $key, 'errors' => $validator->errors()->get("*")];
            } else if (empty($errors)) {

                $activo = [
                    'ciclo_id'          => $request->ciclo_id,
                    'punto_id'          => $zonaObj->idAgenda,
                    'etiqueta'          => $item['etiqueta'],
                    'audit_status'      => $item['audit_status'],
                    'cod_zona'          => $zonaObj->codigoUbicacion,
                    'cod_emplazamiento' => null,
                    'user_id'           => $request->user()->id,
                    'created_at'        => date('Y-m-d H:i:s'),
                    'updated_at'        => date('Y-m-d H:i:s'),
                ];

                $assets[] = $activo;
            }
        }

        //quedan los anteriores obsoletos
        DB::update(
            'UPDATE inv_conteo_registro 
                    SET status = 2, updated_at = NOW() 
                    WHERE ciclo_id = ? AND punto_id = ? AND cod_emplazamiento IS NULL ',
            [$request->ciclo_id, $zonaObj->idAgenda]
        );

        //se insertan los nuevos registros
        DB::table('inv_conteo_registro')->insert($assets);


        return response()->json([
            'status' => 'OK',
            'code'   => 200,
            'message' => 'successfully processed'
        ]);
    }


    /**
     * Process count by Zone multiple users
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function processConteoByZonaMultipleUsers(Request $request)
    {


        if ($request->items) {
            $request->merge(['items' => json_encode($request->items)]);
        }


        $request->validate([
            'items'             => 'required|json',
            'ciclo_id'          => 'required|integer|exists:inv_ciclos,idCiclo',
            'zona_id'           => 'required|integer|exists:ubicaciones_n1,idUbicacionN1',
        ]);


        $zonaObj = ZonaPunto::find($request->zona_id);



        $cicloPunto = InvCicloPunto::where('idCiclo', $request->ciclo_id)->where('idPunto', $zonaObj->idAgenda)->first();

        if (!$cicloPunto) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'La dirección ' . $zonaObj->idAgenda . ' no está asociada al ciclo '
            ], 404);
        }


        $items = json_decode($request->items);

        $cicloObj = $cicloPunto->ciclo;



        $cicloPunto = InvCicloPunto::where('idCiclo', $request->ciclo_id)->where('idPunto', $zonaObj->idAgenda)->first();

        if (!$cicloPunto) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'La dirección ' . $zonaObj->idAgenda . ' no está asociada al ciclo '
            ], 404);
        }


        $items = json_decode($request->items);

        $cicloObj = $cicloPunto->ciclo;

        $etiquetas = ActivoService::getLabelsByCycleAndZone($zonaObj, $cicloObj);


        $items = collect($items)->unique();


        $processedLabels = DB::table("inv_conteo_registro")
            ->where('status', '=', 1)
            ->where('ciclo_id', '=', $request->ciclo_id)
            ->where('punto_id', '=', $zonaObj->idAgenda)
            ->where('cod_zona', '=', $zonaObj->codigoUbicacion)
            ->whereNull('cod_emplazamiento')
            ->get();



        $auditLabelServ = new AuditLabelsService($items->pluck('etiqueta')->toArray(), $etiquetas->toArray(), $processedLabels);

        $result = $auditLabelServ->processAuditedLabels_Zone($request->ciclo_id, $zonaObj->idAgenda, $zonaObj->codigoUbicacion, $request->user()->id);


        if (!empty($result['errors'])) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'errors' => $result['errors'],
                'message' => 'Error en etiquetas '
            ], 422);
        }

        return response()->json([
            'status' => 'OK',
            'code'   => 200,
            'message' => 'successfully processed'
        ]);
    }


    /**
     * Process count by Address multiple users
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function processConteoByAddressMultipleUsers(Request $request)
    {


        if ($request->items) {
            $request->merge(['items' => json_encode($request->items)]);
        }


        $request->validate([
            'items'             => 'required|json',
            'ciclo_id'          => 'required|integer|exists:inv_ciclos,idCiclo',
            'address_id'        => 'required|integer|exists:ubicaciones_geograficas,idUbicacionGeo',
        ]);


        $puntoObj = UbicacionGeografica::find($request->address_id);

        $cicloPunto = InvCicloPunto::where('idCiclo', $request->ciclo_id)->where('idPunto', $request->address_id)->first();

        if (!$cicloPunto) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'La dirección ' . $request->address_id . ' no está asociada al ciclo '
            ], 404);
        }


        $items = json_decode($request->items);

        $cicloObj = $cicloPunto->ciclo;

        $etiquetas = ActivoService::getLabelsByCycleAndAddress($puntoObj, $cicloObj);


        $items = collect($items)->unique();


        $processedLabels = DB::table("inv_conteo_registro")
            ->where('status', '=', 1)
            ->where('ciclo_id', '=', $request->ciclo_id)
            ->where('punto_id', '=', $puntoObj->idUbicacionGeo)
            ->get();



        $auditLabelServ = new AuditLabelsService($items->pluck('etiqueta')->toArray(), $etiquetas->toArray(), $processedLabels);

        $result = $auditLabelServ->processAuditedLabels_Address($request->ciclo_id, $puntoObj->idUbicacionGeo, $request->user()->id);


        if (!empty($result['errors'])) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'errors' => $result['errors'],
                'message' => 'Error en etiquetas '
            ], 422);
        }

        return response()->json([
            'status' => 'OK',
            'code'   => 200,
            'message' => 'successfully processed'
        ]);
    }

    /**
     * Show count by Emplazamiento
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    


     public function deleteSobrantesConteoByEmplazamiento(int $ciclo, int $emplazamiento, Request $request)
     {
        $request->merge(['ciclo_id'         => $ciclo]);
        $request->merge(['emplazamiento_id' => $emplazamiento]);


        $request->validate([
            'ciclo_id'          => 'required|integer|exists:inv_ciclos,idCiclo',
            'emplazamiento_id'  => 'required|integer|exists:ubicaciones_n2,idUbicacionN2',
        ]);


        $empObj = Emplazamiento::find($request->emplazamiento_id);

        $zonaObj = $empObj->zonaPunto;

        $cicloPunto = InvCicloPunto::where('idCiclo', $request->ciclo_id)->where('idPunto', $zonaObj->idAgenda)->first();

        if (!$cicloPunto) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'La dirección ' . $zonaObj->idAgenda . ' no está asociada al ciclo '
            ], 404);
        }

        $data = DB::delete("DELETE FROM inv_conteo_registro 
                    WHERE ciclo_id = ? 
                    AND punto_id = ? 
                    AND cod_emplazamiento = ? 
                    AND audit_status = 3", [
                        $cicloPunto->idCiclo,
                        $zonaObj->idAgenda,
                        $empObj->codigoUbicacion
                    ]);


        return response()->json(['status' => 'OK',   'message' => 'Sobrantes eliminados exitosamente.']);
         
     }
     
    public function showConteoByEmplazamiento(int $ciclo, int $emplazamiento, Request $request)
    {

        $request->merge(['ciclo_id'         => $ciclo]);
        $request->merge(['emplazamiento_id' => $emplazamiento]);


        $request->validate([
            'ciclo_id'          => 'required|integer|exists:inv_ciclos,idCiclo',
            'emplazamiento_id'  => 'required|integer|exists:ubicaciones_n2,idUbicacionN2',
        ]);


        $empObj = Emplazamiento::find($request->emplazamiento_id);

        $zonaObj = $empObj->zonaPunto;

        $cicloPunto = InvCicloPunto::where('idCiclo', $request->ciclo_id)->where('idPunto', $zonaObj->idAgenda)->first();

        if (!$cicloPunto) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'La dirección ' . $zonaObj->idAgenda . ' no está asociada al ciclo '
            ], 404);
        }

        $data = DB::select("SELECT * FROM inv_conteo_registro 
        WHERE ciclo_id = ? AND punto_id = ? AND cod_emplazamiento = ? AND status = 1 ", [
            $cicloPunto->idCiclo,
            $zonaObj->idAgenda,
            $empObj->codigoUbicacion
        ]);

        return response()->json(['status' => 'OK', 'data' => $data]);
    }

    /**
     * Show count by Zone
     *
     * @param  int $ciclo
     * @param  int $zona
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function showConteoByZone(int $ciclo, int $zona, Request $request)
    {

        $request->merge(['ciclo_id'         => $ciclo]);
        $request->merge(['zona_id' => $zona]);


        $request->validate([
            'ciclo_id'          => 'required|integer|exists:inv_ciclos,idCiclo',
            'zona_id'           => 'required|integer|exists:ubicaciones_n1,idUbicacionN1',
        ]);


        $zonaObj = ZonaPunto::find($request->zona_id);



        $cicloPunto = InvCicloPunto::where('idCiclo', $request->ciclo_id)->where('idPunto', $zonaObj->idAgenda)->first();

        if (!$cicloPunto) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'La dirección ' . $zonaObj->idAgenda . ' no está asociada al ciclo '
            ], 404);
        }

        $data = DB::select("SELECT * FROM inv_conteo_registro 
        WHERE ciclo_id = ? AND punto_id = ? AND cod_zona = ? AND cod_emplazamiento IS NULL AND status = 1 ", [
            $cicloPunto->idCiclo,
            $zonaObj->idAgenda,
            $zonaObj->codigoUbicacion
        ]);

        return response()->json(['status' => 'OK', 'data' => $data]);
    }


    /**
     * Show count by Address
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function showConteoByAddress(int $ciclo, int $punto, Request $request)
    {

        $request->merge(['ciclo_id'         => $ciclo]);
        $request->merge(['address_id' => $punto]);


        $request->validate([
            'ciclo_id'          => 'required|integer|exists:inv_ciclos,idCiclo',
            'address_id'        => 'required|integer|exists:ubicaciones_geograficas,idUbicacionGeo',
        ]);


        $puntoObj = UbicacionGeografica::find($request->address_id);



        $cicloPunto = InvCicloPunto::where('idCiclo', $request->ciclo_id)->where('idPunto', $puntoObj->idUbicacionGeo)->first();

        if (!$cicloPunto) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'La dirección ' . $puntoObj->idUbicacionGeo . ' no está asociada al ciclo '
            ], 404);
        }

        $data = DB::select("SELECT * FROM inv_conteo_registro 
        WHERE ciclo_id = ? AND punto_id = ? AND status = 1 ", [
            $cicloPunto->idCiclo,
            $puntoObj->idUbicacionGeo
        ]);

        return response()->json(['status' => 'OK', 'data' => $data]);
    }

    public function deleteSobrantesConteoByAddress(int $ciclo, int $punto, Request $request)
    {

        $request->merge(['ciclo_id'   => $ciclo]);
        $request->merge(['address_id' => $punto]);


        $request->validate([
            'ciclo_id'          => 'required|integer|exists:inv_ciclos,idCiclo',
            'address_id'        => 'required|integer|exists:ubicaciones_geograficas,idUbicacionGeo',
        ]);


        $puntoObj = UbicacionGeografica::find($request->address_id);



        $cicloPunto = InvCicloPunto::where('idCiclo', $request->ciclo_id)->where('idPunto', $puntoObj->idUbicacionGeo)->first();

        if (!$cicloPunto) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'La dirección ' . $puntoObj->idUbicacionGeo . ' no está asociada al ciclo '
            ], 404);
        }

        $data = DB::delete("DELETE FROM inv_conteo_registro 
        WHERE ciclo_id = ? 
        AND punto_id = ? 
        AND audit_status = 3 ", [
            $cicloPunto->idCiclo,
            $puntoObj->idUbicacionGeo
        ]);

        return response()->json(['status' => 'OK', 'message' => 'Sobrantes eliminados exitosamente.']);
    }
  
    public function resetConteoByEmplazamiento(Request $request)
    {



        $request->validate([
            'ciclo_id'          => 'required|integer|exists:inv_ciclos,idCiclo',
            'emplazamiento_id'  => 'required|integer|exists:ubicaciones_n2,idUbicacionN2',
        ]);


        $empObj = Emplazamiento::find($request->emplazamiento_id);

        $zonaObj = $empObj->zonaPunto;

        $cicloPunto = InvCicloPunto::where('idCiclo', $request->ciclo_id)->where('idPunto', $zonaObj->idAgenda)->first();

        if (!$cicloPunto) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'La dirección ' . $zonaObj->idAgenda . ' no está asociada al ciclo '
            ], 404);
        }

        DB::update(
            'UPDATE inv_conteo_registro 
                    SET status = 2, updated_at = NOW(), user_id = ? 
                    WHERE ciclo_id = ? AND punto_id = ? AND cod_emplazamiento = ? ',
            [$request->user()->id, $request->ciclo_id, $zonaObj->idAgenda, $empObj->codigoUbicacion]
        );

        return response()->json(['status' => 'OK', 'message' => 'Realizado exitosamente']);
    }

    public function resetConteoByZona(Request $request)
    {


        $request->validate([
            'ciclo_id'          => 'required|integer|exists:inv_ciclos,idCiclo',
            'zona_id'           => 'required|integer|exists:ubicaciones_n1,idUbicacionN1',
        ]);


        $zonaObj = ZonaPunto::find($request->zona_id);


        $cicloPunto = InvCicloPunto::where('idCiclo', $request->ciclo_id)->where('idPunto', $zonaObj->idAgenda)->first();

        if (!$cicloPunto) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'La dirección ' . $zonaObj->idAgenda . ' no está asociada al ciclo '
            ], 404);
        }

        DB::update(
            'UPDATE inv_conteo_registro 
                    SET status = 2, updated_at = NOW(), user_id = ? 
                    WHERE ciclo_id = ? AND punto_id = ? AND cod_zona = ? AND codigo_emplazamiento IS NULL',
            [$request->user()->id, $request->ciclo_id, $zonaObj->idAgenda, $zonaObj->codigoUbicacion]
        );

        return response()->json(['status' => 'OK', 'message' => 'Realizado exitosamente']);
    }

    /**
     * Show count by Address
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function resetConteoByAddress(Request $request)
    {



        $request->validate([
            'ciclo_id'          => 'required|integer|exists:inv_ciclos,idCiclo',
            'address_id'        => 'required|integer|exists:ubicaciones_geograficas,idUbicacionGeo',
        ]);


        $puntoObj = UbicacionGeografica::find($request->address_id);



        $cicloPunto = InvCicloPunto::where('idCiclo', $request->ciclo_id)->where('idPunto', $puntoObj->idUbicacionGeo)->first();

        if (!$cicloPunto) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'La dirección ' . $puntoObj->idUbicacionGeo . ' no está asociada al ciclo '
            ], 404);
        }

        DB::update(
            'UPDATE inv_conteo_registro 
                    SET status = 2, updated_at = NOW(), user_id = ? 
                    WHERE ciclo_id = ? AND punto_id = ?  ',
            [$request->user()->id, $request->ciclo_id, $puntoObj->idUbicacionGeo]
        );

        return response()->json(['status' => 'OK', 'message' => 'Realizado exitosamente']);
    }

    protected function rules()
    {

        return [

            'etiqueta'      => 'required|string',
        ];
    }
}
