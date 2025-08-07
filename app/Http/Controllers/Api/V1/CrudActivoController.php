<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CrudActivoResource;
use App\Http\Resources\V1\InventariosResource;
use App\Models\CrudActivo;
use App\Models\CategoriaN1;
use App\Models\CategoriaN2;
use App\Models\InvCiclo;
use App\Models\User;
use App\Models\Inv_imagenes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Inventario;
use App\Services\ActivoService;
use App\Services\ImageService;
use Illuminate\Http\Request;
use App\Services\Imagenes\PictureSafinService;
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

public function showActivos($etiqueta)
{
    // Consulta en inv_inventario
    $sql1 = "
        SELECT 
            inv.*, 
            grupos.descripcion_grupo, 
            familias.descripcion_familia
        FROM inv_inventario AS inv
        LEFT JOIN dp_grupos AS grupos ON inv.id_grupo = grupos.id_grupo
        LEFT JOIN dp_familias AS familias ON inv.id_familia = familias.id_familia
        WHERE inv.etiqueta = ?
    ";

    $data = DB::select($sql1, [$etiqueta]);

    //consulta en crud_activos
    if (empty($data)) {
        $sql2 = "
            SELECT 
                crud.*, 
                grupos.descripcion_grupo, 
                familias.descripcion_familia
            FROM crud_activos AS crud
            LEFT JOIN dp_grupos AS grupos ON crud.id_grupo = grupos.id_grupo
            LEFT JOIN dp_familias AS familias ON crud.id_familia = familias.id_familia
            WHERE crud.etiqueta = ?
        ";

        $data = DB::select($sql2, [$etiqueta]);
    }

    return response()->json($data, 200);
}


    public function showAllAssetsByCycleCats(Request $request, $ciclo)
    {


        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json(['status' => 'error', 'message' => 'Not Found', 'code' => 404], 404);
        }


        $activos = $cicloObj->activos_with_cats()->get();


        return response()->json(CrudActivoResource::collection($activos), 200);
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
        $idActivo_Inventario = Inventario::where('etiqueta', '=', $etiqueta)->value('id_inventario');

        if (!$idActivo_Inventario) {
            return response()->json([
                'message' => 'Not Found',
                'status' => 'error'
            ], 404);
        }

        DB::table('inv_inventario')
        ->where('id_inventario', '=', $idActivo_Inventario)
        ->update([
            'crud_activo_estado' => 3
        ]);
        // --- Si es activo de Inventario: subimos imagen a carpeta inventario ---
        // $userFolder = "customers/" . $request->user()->nombre_cliente . "/images/inventario/" . $etiqueta . "/" . now()->format('Y-m-d');

        // if (!Storage::exists($userFolder)) {
        //     Storage::makeDirectory($userFolder);
        // }

    $filename = '9999_' . $etiqueta;
    $origen = 'SAFIN_APP_INVENTARIO';
    $file = $request->file('imagen'); 
    $namefile = $filename . '.jpg'; ;
    $path = $file->storeAs(
    PictureSafinService::getImgSubdir($request->user()->nombre_cliente), 
    $namefile, 
    'taxoImages'
        );

    $url = Storage::disk('taxoImages')->url($path);
    $url_pict = dirname($url) . '/';
        // $imageName = $etiqueta . '_' . uniqid();
        // $path = $this->imageService->optimizeImageinv($file, $userFolder, $imageName);
        // $fullUrl = asset('storage/' . $path);

         if ($request->oldImageUrl) {
            $imagenExistente = Inv_imagenes::where('etiqueta', $etiqueta)
                ->where('url_imagen', $request->oldImageUrl)
                ->first();

            if ($imagenExistente) {
                $imagenExistente->url_imagen = $url_pict;
                $imagenExistente->origen = $origen;
                $imagenExistente->picture = $filename.'.jpg';
                $imagenExistente->updated_at = now();
                $imagenExistente->save();

                return response()->json([
                    'status' => 'OK',
                    'message' => 'Imagen existente actualizada',
                    'url' => $url_pict
                ], 200);
            }
        }
    }

    $filename = '9999_' . $etiqueta;
    $origen = 'SAFIN_APP';

    $file = $request->file('imagen'); 
    $namefile = $filename . '.jpg'; 

   $path = $file->storeAs(
    PictureSafinService::getImgSubdir($request->user()->nombre_cliente), 
    $namefile, 
    'taxoImages'
        );

    $url = Storage::disk('taxoImages')->url($path);
    $url_pict = dirname($url) . '/';
        
       $ultimo = DB::table('crud_activos_pictures')
            ->where('id_activo', $idActivo_Documento)
            ->orderByDesc('id_foto')
            ->first();
//
        if ($ultimo) {
            DB::table('crud_activos_pictures')
                ->where('id_foto', $ultimo->id_foto)
                ->update([
                    'url_picture' => $url_pict,
                    'picture'     => $filename.'.jpg',
                    'origen'      => $origen,
                    'fecha_update' => now()
                ]);
        } else {
            // Insertar si no existe ninguno
            DB::table('crud_activos_pictures')->insert([
                'id_activo'   => $idActivo_Documento,
                'url_picture' => $url_pict,
                'picture'     => $filename.'.jpg',
                'origen'      => $origen,
                'fecha_update' => now()
            ]);
        }

        // $activo->foto4 = $path;

        // $activo->save();

      return response()->json(
    [
        'status' => 'OK',
        'path'   => $path,
        'url'    => $url
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

   public function getIdResponsable()
{
    $usuario = Auth::user()->name;

    $nombre = DB::table('sec_users')
        ->where('login', $usuario)
        ->value('name');

     $idResponsable = DB::table('responsables')
        ->where('name', $nombre) 
        ->value('idResponsable');

    return $idResponsable;
}


    public function update(Request $request, $etiqueta)
    {
        
        $activo = CrudActivo::where('etiqueta', $etiqueta)->first();

        if (!$activo) {

            $activo = Inventario::where('etiqueta', $etiqueta)->first();

            if ($activo) {
                $responsable = $this->getNombre();
                $idResponsable = $this->getIdResponsable() ?? 0;

    
                $activo->update([
                'descripcion_marca'    => $request->descripcion_marca,
                'id_marca'             => $request->id_marca,
                'modelo'               => $request->modelo,
                'serie'                => $request->serie,
                'estado'               => $request->estado,
                'descripcionTipo'      => $request->descripcionTipo,
                'observacion'          => $request->observacion,
                'latitud'              => $request->latitud,
                'longitud'             => $request->longitud,
                'capacidad'            => $request->capacidad,
                'idForma'              => $request->idForma,
                'idMaterial'           => $request->idMaterial,
                'color'                => $request->color,
                'tipo_trabajo'         => $request->tipo_trabajo,
                'carga_trabajo'        => $request->carga_trabajo,
                'estado_operacional'   => $request->estado_operacional,
                'estado_conservacion'  => $request->estado_conservacion,
                'condicion_ambiental'  => $request->condicion_ambiental,
                'eficiencia'           => $request->eficiencia,
                'texto_abierto_1'      => $request->texto_abierto_1,
                'texto_abierto_2'      => $request->texto_abierto_2,
                'texto_abierto_3'      => $request->texto_abierto_3,
                'texto_abierto_4'      => $request->texto_abierto_4,
                'texto_abierto_5'      => $request->texto_abierto_5,
                'responsable'          => $responsable,
                'idResponsable'        => $idResponsable,
                'crud_activo_estado'   => 3
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

           $responsable = $this->getIdResponsable();

              $activo->update([
                'marca'               => $request->id_marca,
                'modelo'              => $request->modelo,
                'serie'               => $request->serie,
                'material'            => $request->idMaterial,
                'forma'               => $request->idForma,
                'color'               => $request->color,
                'estadoConservacion'  => $request->estado_conservacion,
                'estadoOperacional'   => $request->estado_operacional,
                'tipoTrabajo'         => $request->tipo_trabajo,
                'cargaTrabajo'        => $request->carga_trabajo,
                'condicionAmbiental'  => $request->condicion_ambiental,
                'capacidad'           => $request->capacidad,
                'eficiencia'          => $request->eficiencia,
                'texto_abierto_1'     => $request->texto_abierto_1 ?? '',
                'texto_abierto_2'     => $request->texto_abierto_2  ?? '',
                'texto_abierto_3'     => $request->texto_abierto_3  ?? '',
                'texto_abierto_4'     => $request->texto_abierto_4  ?? '',
                'texto_abierto_5'     => $request->texto_abierto_5  ?? '',
                'responsableN1'       => $responsable,
                'apoyaBrazosRuedas'   => $request->estado,
                'descripcionTipo'     => $request->descripcionTipo,
                'observacion'         => $request->observacion,
                'latitud'             => $request->latitud,
                'longitud'            => $request->longitud
            ]);

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
