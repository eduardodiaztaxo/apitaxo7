<?php

namespace App\Http\Controllers\Api\V1\Responsible;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CrudActivoLiteResource;
use App\Models\CrudActivo;
use App\Models\Responsable;
use App\Models\SecScUser;
use App\Models\SolicitudAsignacion;
use App\Services\Documents\ActaEntrega;
use App\Services\Documents\ActaHelperService;
use App\Services\Documents\QRCode\QRCode;
use App\Services\SolicitudService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class AssignResponsibleController extends Controller
{


    private $solicitudService;

    public function __construct(SolicitudService $solicitudService)
    {
        $this->solicitudService = $solicitudService;
    }
    /**
     * Assign tags to responsible.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function prepareAssignTags(Request $request)
    {

        $request->validate([
            'etiquetas'         => 'required|array',
            'responsible_id'    => 'required|exists:responsables,idResponsable'
        ]);

        $username = $request->user()->name;

        try {



            $solicitud = SolicitudAsignacion::create([
                'n_solicitud'   => $this->solicitudService->getNextIdMov(),
                'fecha'         => date('Y-m-d'),
                'fecha_mov'     => date('Y-m-d H:i:s'),
                'usuario'       => $username,
                'comentario'    => $request->comentario,
                'estado_proceso' => 500,
                'estado_docto'  => 1,
                'id_responsable' => $request->responsable_id,
                'tipo'          => 'D'
            ]);

            CrudActivo::whereIn('etiqueta', $request->etiquetas)
                ->where('tipoCambio', '=', '0')
                ->where('responsableN1', '=', '0')
                ->update(['tipocambio' => 500, 'usuario' => $username, 'idActa' => $solicitud->n_solicitud]);

            return response()->json(
                [
                    'status' => 'OK',
                    'message' => 'Realizado Exitosamente',
                    'code'  => 201,
                    'data' => CrudActivoLiteResource::collection(CrudActivo::whereIn('etiqueta', $request->etiquetas)->get())
                ],
                201
            );
        } catch (\Exception $e) {

            return response()->json([
                'status'    => 'error',
                'message'   => 'No se pudo crear el responsable',
                'code'      => 422
            ], 422);
        }
    }


    /**
     * Assign tags to responsible.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sendBlankDocument(Request $request)
    {


        $request->validate([
            'etiquetas'         => 'required|array',
            'responsible_id'    => 'required|exists:responsables,idResponsable'
        ]);

        $responsable = Responsable::find($request->responsible_id);

        $solicitud = $responsable->solicitudesAsignacion()
            ->where('estado_proceso', '=', 500)
            ->where('estado_docto', '=', 0)
            ->orderByDesc('id_solicitud')
            ->first();


        if (!$solicitud) {
            return response()->json([
                'status' => 'error',
                'message' => 'No existe solicitud de asignación en curso',
                404
            ], 404);
        }

        $sc_user = SecScUser::find($request->user()->name);


        if (!$sc_user) {
            return response()->json([
                'status' => 'error',
                'message' => 'No existe registro de quien entrega',
                404
            ], 404);
        }


        $actaHelper = new ActaHelperService();

        $assets = CrudActivo::whereIn('etiqueta', $request->etiquetas)->get();

        $path = $actaHelper->createActa($assets, $responsable, $sc_user, $solicitud);




        return response()->json(
            [
                'status' => 'OK',
                'message' => 'Realizado Exitosamente',
                'code'  => 201,
                'path' => $path
            ],
            201
        );
    }
}
