<?php

namespace App\Http\Controllers\Api\V1\Responsible;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CrudActivoLiteResource;
use App\Mail\AssignResponsibleMail;
use App\Mail\SendBlankDocument;
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
use Illuminate\Support\Facades\Mail;

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
            'responsible_id'    => 'required|exists:responsables,idResponsable',
            'comentario'        => 'required|string|max:255'
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
                'id_responsable' => $request->responsible_id,
                'tipo'          => 'A'
            ]);

            CrudActivo::whereIn('etiqueta', $request->etiquetas)
                ->where('tipoCambio', '=', '0')
                ->where('responsableN1', '=', '0')
                ->update(['tipoCambio' => 500, 'usuario' => $username, 'idActa' => $solicitud->n_solicitud]);

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
                'message'   => 'No se pudo realizar la solicitud',
                'code'      => 422,
                'data'      => $e
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
            ->where('estado_docto', '=', 1)
            ->orderByDesc('id_solicitud')
            ->first();





        if (!$solicitud) {
            return response()->json([
                'status' => 'error',
                'message' => 'No existe solicitud de asignación en curso',
                404
            ], 404);
        }


        $assets = CrudActivo::whereIn('etiqueta', $request->etiquetas)->where('idActa', '=', $solicitud->n_solicitud)->get();

        if (!$assets || $assets->count() === 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'No existen bienes asociados a la solicitud de asignación',
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



        $path = $actaHelper->createActa($assets, $responsable, $sc_user, $solicitud);


        $mail = Mail::to([$responsable->mail]);

        if (isset($sc_user->email)) {
            $mail->cc([$sc_user->email]);
        }


        $mail->send(new SendBlankDocument($solicitud, $path));

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



    /**
     * Confirm assign tags to responsible and sign document.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function signDocumentAndConfirmResponsible(Request $request)
    {


        $request->validate([

            'etiquetas'             => 'required|array',
            'responsible_id'        => 'required|exists:responsables,idResponsable',

            'responsible_signature' => [
                'required_without:files',
                'string',
                'regex:/^data:image\/png;base64,/'
            ],


            'files.*' => 'required_without:responsible_signature|file|mimes:pdf,jpg,jpeg,png|max:2048',


        ]);



        $responsable = Responsable::find($request->responsible_id);



        $solicitud = $responsable->solicitudesAsignacion()
            ->where('estado_proceso', '=', 500)
            ->where('estado_docto', '=', 1)
            ->orderByDesc('id_solicitud')
            ->first();





        if (!$solicitud) {
            return response()->json([
                'status' => 'error',
                'message' => 'No existe solicitud de asignación en curso',
                404
            ], 404);
        }


        $assets = CrudActivo::whereIn('etiqueta', $request->etiquetas)->where('idActa', '=', $solicitud->n_solicitud)->get();

        

        if (!$assets || $assets->count() === 0) {
            return response()->json([
                'status' => 'error',
                'message' => $solicitud,
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


        $doctos = [];

        if ($request->responsible_signature) {



            $actaHelper = new ActaHelperService();



            $path = $actaHelper->createActa($assets, $responsable, $sc_user, $solicitud, $request->responsible_signature);

            //Solicitud Asignación Completada
            $solicitud->acta = $path;

            $doctos[] = $path;
        } else if ($request->files) {


            foreach ($request->file('files') as $key => $file) {

                $namefile = $file->getClientOriginalName();

                $namefile = pathinfo($namefile, PATHINFO_FILENAME);

                $extension = $file->getClientOriginalExtension();

                $namefile = preg_replace('/[^a-zA-Z0-9]/', '', $namefile);

                $namefile = substr($namefile, 0, 32);

                $namefile = $key . "_" . date('YmdHis') . "_" . $namefile . "." . $extension;

                $subdir = "/documents/acta-entrega/";

                $path = $file->storeAs('actas/documents/acta-entrega', $namefile, 'local'); // Save files to 'storage/app/actas'

                $doctos[] = "/" . $path;
            }


            $actaHelper = new ActaHelperService();

            $actaHelper->saveFilePaths($doctos, $solicitud);
        }


        $solicitud->estado_docto = 2;
        $solicitud->estado_proceso = 596;
        $solicitud->save();

        foreach ($assets as $asset) {


            $asset->responsableN1 = $responsable->idResponsable;
            $asset->fechaModificacion = date('Y-m-d H:i:s');
            $asset->tipoCambio = 596;
            $asset->idActa = $solicitud->n_solicitud;

            $asset->save();
        }








        $mail = Mail::to([$responsable->mail]);

        if (isset($sc_user->email)) {
            $mail->cc([$sc_user->email]);
        }


        $mail->send(new AssignResponsibleMail($solicitud, $doctos));

        return response()->json(
            [
                'status' => 'OK',
                'message' => 'Realizado Exitosamente',
                'code'  => 201,
                'doctos' => $doctos
            ],
            201
        );
    }
}
