<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CrudActivoResource;
use App\Models\CrudActivo;
use App\Services\ActivoService;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Laravel\Facades\Image as Image;

class CrudActivoController extends Controller
{


    private $activoService;
    private $imageService;

    public function __construct(ActivoService $activoService, ImageService $imageService)
    {
        $this->activoService = $activoService;
        $this->imageService  = $imageService;
    }



    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($etiqueta)
    {
        //
        return $etiqueta;
    }


    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request $request 
     * @param int  $etiqueta
     * @return \Illuminate\Http\Response
     */
    public function showByEtiqueta(Request $request, $etiqueta)
    {
        //
        $activo = CrudActivo::where('etiqueta', '=', $etiqueta)->first();

        if (!$activo) {
            return response()->json([
                "message" => "Not Found",
                "status"  => "error"
            ], 404);
        }



        $activo->requireUbicacion = 1;

        $activo->requireEmplazamiento = 1;

        $resource = new CrudActivoResource($activo);

        return response()->json($resource, 200);
    }


    public function uploadImageByEtiqueta(Request $request, $etiqueta)
    {

        //\\10.3.126.1\taxo_files\SAFIN\nombre_cliente\img

        $request->validate([
            'imagen' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        $activo = CrudActivo::where('etiqueta', '=', $etiqueta)->first();

        if (!$activo) {
            return response()->json([
                "message" => "Not Found",
                "status"  => "error"
            ], 404);
        }

        if ($activo->foto4)
            $this->imageService->deleteImage($activo->foto4);

        $path = $this->imageService->optimizeImageAndSave(
            $request->file('imagen'),
            "customers/" . $request->user()->nombre_cliente . "/images",
            $etiqueta . "_" . date('YmdHis')
        );

        $activo->foto4 = $path;

        $activo->save();

        return response()->json(
            [
                'status'    => 'OK',
                'path'      => $path,
                'url'       => asset('storage/' . $path)
            ],
            201
        );
    }




    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request     $request
     * @param  int                          $etiqueta
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $etiqueta)
    {
        $request->validate([
            'marca'         =>  'required|integer|exists:indices_listas,idLista',
            'modelo'        =>  'required',
            'serie'         =>  'required',
            'responsable'   =>  'sometimes|integer|exists:responsables,idResponsable',
            'estado_bien'   =>  'required|exists:indices_listas_13,idLista'
        ]);

        $activo = CrudActivo::where('etiqueta', '=', $etiqueta)->first();

        if (!$activo) {
            return response()->json([
                "message" => "Not Found",
                "status"  => "error"
            ], 404);
        }

        if ($request->responsable) {
            $request->merge(['responsableN1' => $request->responsable]);
        }

        $request->merge(['apoyaBrazosRuedas' => $request->estado_bien]);


        $activo->fill(
            $request->only([
                'marca',
                'modelo',
                'serie',
                'responsableN1',
                'apoyaBrazosRuedas'
            ])
        );

        $activo->save();

        return response()->json(
            [
                'status'    => 'OK',
                'code'      => 200,
                'data'      => CrudActivoResource::make($activo)
            ],
            200
        );
    }


    public function marcasDisponibles(Request $request, $etiqueta)
    {

        $activo = CrudActivo::where('etiqueta', '=', $etiqueta)->first();

        if (!$activo) {
            return response()->json([
                "message" => "Not Found",
                "status"  => "error"
            ], 404);
        }


        $collection = $activo->marcasDisponibles()->get();
        return response()->json($collection, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
