<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CrudActivoResource;
use App\Http\Resources\V1\InventariosResource;
use App\Models\CrudActivo;
use App\Models\CategoriaN1;
use App\Models\CategoriaN2;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Inventario;
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
            $etiqueta = Inventario::where('etiqueta', '=', $etiqueta)->first();
            $resource = new InventariosResource($etiqueta);
            return response()->json($resource, 200);
        }

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


    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request $request 
     * @return \Illuminate\Http\Response
     */
    public function showByEtiquetas(Request $request)
    {

        $request->validate([
            'etiquetas' => 'required|array',
        ]);

        $etiquetas = $request->etiquetas;

        //
        $activos = CrudActivo::whereIn('etiqueta', $etiquetas)->get();



        if (empty($activos)) {
            return response()->json([
                "message" => "Not Found",
                "status"  => "error"
            ], 404);
        }

        return response()->json(CrudActivoResource::collection($activos), 200);
    }


    /**
     * Display the specified resource.
     * show tags without responsibles and available
     *
     * @param  \Illuminate\Http\Request $request 
     * @return \Illuminate\Http\Response
     */
    public function showByEtiquetasWithoutResponsibles(Request $request)
    {

        $request->validate([
            'etiquetas' => 'required|array',
        ]);

        $etiquetas = $request->etiquetas;

        //Disponibles y sin responsables
        $activos = CrudActivo::whereIn('etiqueta', $etiquetas)
            ->where('tipoCambio', '=', '0')
            ->where(function ($query) {
                $query->where('responsableN1', '=', '0')
                    ->orWhereNull('responsableN1');
            })->get();



        if (empty($activos)) {
            return response()->json([
                "message" => "Not Found",
                "status"  => "error"
            ], 404);
        }

        return response()->json(CrudActivoResource::collection($activos), 200);
    }

    public function uploadImageByEtiqueta(Request $request, $etiqueta)
    {

        //\\10.3.126.1\taxo_files\SAFIN\nombre_cliente\img

        $request->validate([
            'imagen' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        // $activo = CrudActivo::where('etiqueta', '=', $etiqueta)->first();

        $idActivo_Documento = CrudActivo::where('etiqueta', '=', $etiqueta)->value('idActivo');


        if (!$idActivo_Documento) {
            return response()->json([
                "message" => "Not Found",
                "status"  => "error"
            ], 404);
        }

        // if (!$activo) {
        //     return response()->json([
        //         "message" => "Not Found",
        //         "status"  => "error"
        //     ], 404);
        // }

        // if ($activo->foto4)
        //     $this->imageService->deleteImage($activo->foto4);

        $path = $this->imageService->optimizeImageAndSave(
            $request->file('imagen'),
            "customers/" . $request->user()->nombre_cliente . "/images",
            $etiqueta . "_" . date('YmdHis')
        );

        $url = asset('storage/' . $path);
        
        $existingRecord = DB::table('crud_activos_foto_docto')->where('idActivo', $idActivo_Documento)->first();

        if ($existingRecord) {
            // Actualizar
            DB::table('crud_activos_foto_docto')
                ->where('idActivo', $idActivo_Documento)
                ->update(['foto_1' => $url]);
        } else {
            // Insertar
            DB::table('crud_activos_foto_docto')->insert([
                'idActivo' => $idActivo_Documento,
                'foto_1'   => $url,
            ]);
        }

        // $activo->foto4 = $path;

        // $activo->save();

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
    public function getNombre()
    {
        $user = Auth::user();
        $usuario = $user->name;
        $responsable = DB::table('sec_users')->where('login', $usuario)->value('name');
        return $responsable;
    }
    public function update(Request $request, $etiqueta)
    {
        $request->validate([
            'marca'            =>  'required|integer|exists:indices_listas,idLista',
            'descripcion_marca' => 'required',
            'modelo'           =>  'required',
            'serie'            =>  'required',
            'responsable'      =>  'sometimes|integer|exists:responsables,idResponsable',
            'estado_bien'      =>  'required|exists:indices_listas_13,idLista',
            'descripcionTipo'  =>  'required',
            'observacion'      =>  'required',
            'latitud'          =>  'required',
            'longitud'         =>  'required'
        ]);

        $activo = CrudActivo::where('etiqueta', $etiqueta)->first();

        if (!$activo) {

            $activo = Inventario::where('etiqueta', $etiqueta)->first();

            if ($activo) {
                $responsable = $this->getNombre();

                $activo->update([
                    'descripcion_marca' => $request->descripcion_marca,
                    'modelo'            => $request->modelo,
                    'serie'             => $request->serie,
                    'estado'            => $request->estado_bien,
                    'descripcionTipo'   => $request->descripcionTipo,
                    'observacion'       => $request->observacion,
                    'latitud'           => $request->latitud,
                    'longitud'          => $request->longitud,
                    'responsable'       => $responsable,
                ]);

                return response()->json([
                    'status'  => 'OK',
                    'code'    => 200,
                    'message' => $activo,
                ], 200);
            } else {
                return response()->json([
                    "message" => "Not Found",
                    "status"  => "error"
                ], 404);
            }
        } else {

            if ($request->responsable) {
                $request->merge(['responsableN1' => $request->responsable]);
            }

            $request->merge(['apoyaBrazosRuedas' => $request->estado_bien]);

            $activo->fill($request->only([
                'marca',
                'modelo',
                'serie',
                'responsableN1',
                'apoyaBrazosRuedas',
                'descripcionTipo',
                'observacion',
                'latitud',
                'longitud'
            ]));

            $activo->save();

            $activo->requireUbicacion = 1;
            $activo->requireEmplazamiento = 1;

            return response()->json([
                'status' => 'OK',
                'code'   => 200,
                'data'   => CrudActivoResource::make($activo)
            ], 200);
        }
    }

    public function marcasDisponibles(Request $request, $etiqueta)
    {

        $activo = CrudActivo::where('etiqueta', '=', $etiqueta)->first();

        if (!$activo) {
            $activo = Inventario::where('etiqueta', '=', $etiqueta)->first();
        }


        $collection = $activo->marcasDisponibles()->get();
        return response()->json($collection, 200);
    }
    public function Localizacion(Request $request, $etiqueta)
    {

        $activo = CrudActivo::where('etiqueta', '=', $etiqueta)->first();

        if (!$activo) {
            return response()->json([
                "message" => "Not Found",
                "status"  => "error"
            ], 404);
        }


        $collection = $activo->Localizacion()->get();
        return response()->json($collection, 200);
    }
    public function categoriasNivel1()
    {


        $collection = CategoriaN1::all();

        return response()->json($collection, 200);
    }
    public function categoriasNivel2($codigoCategoria)
    {
        $collection = CategoriaN2::where('codigoCategoria', 'LIKE', $codigoCategoria . '%')->get();

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
