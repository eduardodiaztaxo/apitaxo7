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
use App\Services\ActivoFinderService;
use App\Services\ProyectoUsuarioService;
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
 
   public function showByEtiqueta(Request $request, $etiqueta, $ciclo)
    {
        
        $activo = ActivoFinderService::findByEtiquetaAndCiclo($etiqueta, $ciclo);

        if (!$activo) {
            return response()->json([
                "message" => "Not Found",
                "status"  => "error"
            ], 404);
        }

        if ($activo instanceof Inventario) {
            return response()->json(new InventariosResource($activo), 200);
        }

        $activo->requireUbicacion = 1;
        $activo->requireEmplazamiento = 1;

        return response()->json(new CrudActivoResource($activo), 200);
    }


    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request $request 
     * @param int  $id
     * @return \Illuminate\Http\Response
     */
    public function showInventoryByID(Request $request, $id)
    {

        $activo = Inventario::find($id);

        if (!$activo) {
            return response()->json([
                "message" => "Not Found",
                "status"  => "error"
            ], 404);
        }

        $resource = new InventariosResource($activo);

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
        $request->validate([
            'imagen' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        $idActivo = ActivoFinderService::getIdByEtiquetaAndCiclo($etiqueta, $request->cycle_id);
        $id_proyecto = ActivoFinderService::getIdProyectoByCiclo($request->cycle_id);

        if (!$idActivo) {
            return response()->json([
                'message' => 'Not Found',
                'status' => 'error'
            ], 404);
        }

        if (!$id_proyecto) {
            //caso de scan bienes
            $id_proyecto = ProyectoUsuarioService::getIdProyecto();
        }

        $ciclo = InvCiclo::find($request->cycle_id);
        $esInventario = $ciclo && $ciclo->idTipoCiclo == ActivoFinderService::TIPO_INVENTARIO;

        if ($esInventario) {
            // Actualizar estado en inventario
            DB::table('inv_inventario')
                ->where('id_inventario', '=', $idActivo)
                ->update(['crud_activo_estado' => 3]);

            $filename = $id_proyecto . '_' . $etiqueta;
            $origen = 'SAFIN_APP_ACTUALIZADA_IMAGEN';
            $file = $request->file('imagen');
            $namefile = $filename . '.jpg';

            $path = $file->storeAs(
                PictureSafinService::getImgSubdir($request->user()->nombre_cliente),
                $namefile,
                'taxoImages'
            );

            $url = Storage::disk('taxoImages')->url($path);
            $url_pict = dirname($url) . '/';

            if ($request->oldImageUrl) {
                $imagenExistente = Inv_imagenes::where('etiqueta', $etiqueta)
                    ->where('id_proyecto', $id_proyecto)
                    ->where('url_imagen', $request->oldImageUrl)
                    ->first();

                if ($imagenExistente) {
                    $imagenExistente->url_imagen = $url_pict . $filename . '.jpg';
                    $imagenExistente->url_picture = $url_pict;
                    $imagenExistente->origen = $origen;
                    $imagenExistente->picture = $filename . '.jpg';
                    $imagenExistente->updated_at = now();
                    $imagenExistente->id_proyecto = $id_proyecto;
                    $imagenExistente->save();

                    return response()->json([
                        'status' => 'OK',
                        'message' => 'Imagen existente actualizada',
                        'url' => $url_pict . $filename . '.jpg'
                    ], 200);
                }
            }

            // Si no hay imagen existente, crear nueva
            $nuevaImagen = new Inv_imagenes();
            $nuevaImagen->etiqueta = $etiqueta;
            $nuevaImagen->id_proyecto = $id_proyecto;
            $nuevaImagen->url_imagen = $url_pict . $filename . '.jpg';
            $nuevaImagen->url_picture = $url_pict;
            $nuevaImagen->origen = $origen;
            $nuevaImagen->picture = $filename . '.jpg';
            $nuevaImagen->created_at = now();
            $nuevaImagen->updated_at = now();
            $nuevaImagen->save();

            return response()->json([
                'status' => 'OK',
                'message' => 'Nueva imagen creada',
                'path' => $path,
                'url' => $url_pict . $filename . '.jpg'
            ], 201);
        }

        // Es auditoría (crud_activos)
        $filename = $id_proyecto . '_' . $etiqueta;
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
            ->where('id_activo', $idActivo)
            ->orderByDesc('id_foto')
            ->first();

        if ($ultimo) {
            DB::table('crud_activos_pictures')
                ->where('id_foto', $ultimo->id_foto)
                ->update([
                    'url_picture' => $url_pict,
                    'picture' => $filename . '.jpg',
                    'origen' => $origen,
                    'fecha_update' => now(),
                    'idProyecto'   => $id_proyecto,
                ]);
        } else {
            DB::table('crud_activos_pictures')->insert([
                'id_activo' => $idActivo,
                'url_picture' => $url_pict,
                'picture' => $filename . '.jpg',
                'origen' => $origen,
                'fecha_update' => now(),
                'idProyecto'   => $id_proyecto,
            ]);
        }

        return response()->json([
            'status' => 'OK',
            'path' => $path,
            'url' => $url
        ], 201);
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
            return response()->json([
                "message" => "Not Found",
                "status"  => "error"
            ], 404);
        }

        $responsable = $this->getIdResponsable();

        $activo->update([
            'marca'               => $request->id_marca,
            'modelo'              => $request->modelo,
            'serie'               => $request->serie,
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

//se agrego el ciclo para las marcas disponibles
    public function marcasDisponibles(Request $request, $etiqueta, $ciclo)
    {
        $activo = ActivoFinderService::findByEtiquetaAndCiclo($etiqueta, $ciclo);

        if (!$activo) {
            return response()->json([
                "message" => "Not Found",
                "status"  => "error"
            ], 404);
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
