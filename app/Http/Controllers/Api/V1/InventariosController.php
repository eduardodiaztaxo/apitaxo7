<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Models\Inventario;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\Inv_imagenes;
use App\Http\Controllers\Controller;
use App\Models\CrudActivo;
use App\Models\InvCiclo;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;
use App\Services\ImageService;
use DateTime;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class InventariosController extends Controller
{
    private $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService  = $imageService;
    }
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


public function createinventario(Request $request)
    {
        $request->validate([
            'id_grupo'              => 'required|string',
            'id_familia'            => 'required|string',
            'etiqueta'              => 'required|string',
            'id_ciclo'              => 'required|exists:inv_ciclos,idCiclo',
            'codigoUbicacion'       => 'required',
        ]);

        $etiquetaInventario = DB::table('inv_inventario')->where('etiqueta', $request->etiqueta)->value('etiqueta');
        $etiquetaUnicaCrudActivo = DB::table('crud_activos')->where('etiqueta', $request->etiqueta)->value('etiqueta');

        if ($etiquetaInventario || $etiquetaUnicaCrudActivo) {
            return response('La etiqueta ya existe', 400);
        }

        if ($request->idUbicacionN2 > 0 && $request->codigoUbicacion_N1 > 0) {
            $codigoUbicacion_N1 = $request->codigoUbicacion_N1;
            $idUbicacionN2 = $request->idUbicacionN2;
        } else {
            $codigoUbicacion_N1 = null;
            if (!empty($request->codigoUbicacion)) {
                $codigoUbicacion_N1 = substr(strval($request->codigoUbicacion), 0, 2);
            }
            $idUbicacionN2 = DB::table('ubicaciones_n2')
                ->where('codigoUbicacion', $request->codigoUbicacion)
                ->value('idUbicacionN2');
        }

        $idUbicacionGeo = DB::table('ubicaciones_n2')
            ->where('idUbicacionN2',  $idUbicacionN2 )
            ->value('idAgenda');

        if ($request->cloneFichaDetalle == "true") {
            $imagenes = DB::table('inv_imagenes')
                ->where('id_img', $request->id_img_clone)
                ->get();

            $url_img = DB::table('inv_imagenes')->max('id_img') + 1;

            foreach ($imagenes as $img) {
                DB::table('inv_imagenes')->insert([
                    'id_img'     => $url_img,
                    'etiqueta'   => $request->etiqueta,
                    'url_imagen' => $img->url_imagen,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } else {

            $id_img = DB::table('inv_imagenes')
                ->where('etiqueta', $request->etiqueta)
                ->orderBy('id_img', 'desc')
                ->value('id_img');
            $url_img = $id_img ?? null;
        }

        $inventario = new Inventario();
        $inventario->id_grupo            = $request->id_grupo;
        $inventario->id_familia          = $request->id_familia;
        $inventario->descripcion_bien    = $request->descripcion_bien;
        $inventario->id_bien             = intval($request->id_bien ?? null);
        $inventario->descripcion_marca   = $request->descripcion_marca ?? 'null';
        $inventario->id_marca            = intval($request->id_marca ?? null);
        $inventario->idForma             = intval($request->idForma ?? null);
        $inventario->idMaterial          = intval($request->idMaterial ?? null);
        $inventario->etiqueta            = $request->etiqueta;
        $inventario->modelo              = $request->modelo ?? 'null';
        $inventario->serie               = $request->serie ?? 'null';
        $inventario->latitud             = $request->latitud ?? null;
        $inventario->longitud            = $request->longitud ?? null;
        $inventario->capacidad           = intval($request->capacidad ?? null);
        $inventario->estado              = intval($request->estado ?? null);
        $inventario->color               = intval($request->color ?? null);
        $inventario->tipo_trabajo        = intval($request->tipo_trabajo ?? null);
        $inventario->carga_trabajo       = intval($request->carga_trabajo ?? null);
        $inventario->estado_operacional  = intval($request->estado_operacional ?? null);
        $inventario->estado_conservacion = intval($request->estado_conservacion ?? null);
        $inventario->condicion_ambiental = intval($request->condicion_ambiental ?? null);
        $inventario->cantidad_img        = $request->cantidad_img;
        $inventario->id_img              = $url_img;
        $inventario->id_ciclo            = $request->id_ciclo;
        $inventario->idUbicacionGeo      = $idUbicacionGeo;
        $inventario->idUbicacionN2       = $idUbicacionN2;
        $inventario->codigoUbicacion_N1  = $codigoUbicacion_N1;
        $inventario->responsable         = $this->getNombre();
        $inventario->idResponsable       = $this->getIdResponsable();
        $inventario->save();

        return response()->json($inventario, 201);
    }


   public function updateinventario(Request $request)
{
   $request->validate([
    'id_grupo'   => 'required|integer',
    'id_familia' => 'required|integer',
    'etiqueta'   => 'required|string',
    'id_ciclo'   => 'required|integer|exists:inv_ciclos,idCiclo',
]);

    $id_img = DB::table('inv_imagenes')
        ->where('etiqueta', $request->etiqueta)
        ->orderBy('id_img', 'desc')
        ->value('id_img');

    $url_img = $id_img ?? null;

    Inventario::where('etiqueta', $request->etiqueta)->update([
        'idForma'             => intval($request->idForma ?? null),
        'idMaterial'          => intval($request->idMaterial ?? null),
        'latitud'             => $request->latitud ?? null,
        'longitud'            => $request->longitud ?? null,
        'capacidad'           => intval($request->capacidad ?? null),
        'estado'              => intval($request->estado ?? null),
        'color'               => intval($request->color ?? null),
        'tipo_trabajo'        => intval($request->tipo_trabajo ?? null),
        'carga_trabajo'       => intval($request->carga_trabajo ?? null),
        'estado_operacional'  => intval($request->estado_operacional ?? null),
        'estado_conservacion' => intval($request->estado_conservacion ?? null),
        'condicion_ambiental' => intval($request->condicion_ambiental ?? null),
        'cantidad_img'        => $request->cantidad_img,
        'id_img'              => $url_img,
        'responsable'         => $this->getNombre(),
        'idResponsable'       => $this->getIdResponsable(),
        'update_inv'          => 0
    ]);

    $inventarioActualizado = Inventario::where('etiqueta', $request->etiqueta)->first();

    return response()->json([
        'message'    => 'Inventario actualizado con éxito',
        'inventario' => $inventarioActualizado
    ], 200);
}


    public function configuracion($id_grupo)
    {
        $sql = "SELECT 
                COALESCE(MAX(CASE WHEN id_atributo = 2 THEN id_validacion END), 0) AS conf_marca,
                COALESCE(MAX(CASE WHEN id_atributo = 3 THEN id_validacion END), 0) AS conf_modelo,
                COALESCE(MAX(CASE WHEN id_atributo = 3 THEN id_tipo_dato END), 0) AS tipo_dato_mod,
                COALESCE(MAX(CASE WHEN id_atributo = 3 THEN valor_minimo END), 0) AS lench_Min_mod,
                COALESCE(MAX(CASE WHEN id_atributo = 3 THEN valor_maximo END), 0) AS lench_Max_mod,
                COALESCE(MAX(CASE WHEN id_atributo = 4 THEN id_validacion END), 0) AS conf_capacidad,
                COALESCE(MAX(CASE WHEN id_atributo = 4 THEN id_tipo_dato END), 0) AS tipo_dato_cap,
                COALESCE(MAX(CASE WHEN id_atributo = 4 THEN valor_minimo END), 0) AS lench_Min_cap,
                COALESCE(MAX(CASE WHEN id_atributo = 4 THEN valor_maximo END), 0) AS lench_Max_cap,
                COALESCE(MAX(CASE WHEN id_atributo = 6 THEN id_validacion END), 0) AS conf_material,
                COALESCE(MAX(CASE WHEN id_atributo = 7 THEN id_validacion END), 0) AS conf_forma,
                COALESCE(MAX(CASE WHEN id_atributo = 8 THEN id_validacion END), 0) AS conf_estado,
                COALESCE(MAX(CASE WHEN id_atributo = 9 THEN id_validacion END), 0) AS conf_estado_operacional,
                COALESCE(MAX(CASE WHEN id_atributo = 10 THEN id_validacion END), 0) AS conf_serie,
                COALESCE(MAX(CASE WHEN id_atributo = 10 THEN id_tipo_dato END), 0) AS tipo_dato_serie,
                COALESCE(MAX(CASE WHEN id_atributo = 10 THEN valor_minimo END), 0) AS lench_Min_serie,
                COALESCE(MAX(CASE WHEN id_atributo = 10 THEN valor_maximo END), 0) AS lench_Max_serie,
                COALESCE(MAX(CASE WHEN id_atributo = 14 THEN id_validacion END), 0) AS conf_color,
                COALESCE(MAX(CASE WHEN id_atributo = 18 THEN id_validacion END), 0) AS conf_estado_conservacion,
                COALESCE(MAX(CASE WHEN id_atributo = 19 THEN id_validacion END), 0) AS conf_tipo_trabajo,
                COALESCE(MAX(CASE WHEN id_atributo = 20 THEN id_validacion END), 0) AS conf_carga_trabajo,
                COALESCE(MAX(CASE WHEN id_atributo = 21 THEN id_validacion END), 0) AS conf_condicion_ambiental,
                COALESCE(MAX(CASE WHEN id_atributo = 22 THEN valor_minimo END), 0) AS lench_Min_etiqueta,
                COALESCE(MAX(CASE WHEN id_atributo = 22 THEN valor_maximo END), 0) AS lench_Max_etiqueta,
                COALESCE(MAX(CASE WHEN id_atributo = 22 THEN tipo_etiqueta END), 0) AS tipo_etiqueta,
                COALESCE(MAX(CASE WHEN id_atributo = 23 THEN id_validacion END), 0) AS conf_latitud,
                COALESCE(MAX(CASE WHEN id_atributo = 24 THEN id_validacion END), 0) AS conf_longitud
            FROM inv_atributos 
            WHERE id_grupo = ?";

        $validacion = DB::select($sql, [$id_grupo]);

        return response()->json($validacion, 200);
    }



    public function ImageByEtiqueta(Request $request, $etiqueta)
    {
        $request->validate([
            'imagenes.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        $userFolder = "customers/" . $request->user()->nombre_cliente . "/images/inventario/" . $etiqueta . "/" . now()->format('Y-m-d');

        if (!Storage::exists($userFolder)) {
            Storage::makeDirectory($userFolder);
        }

        $paths = [];
        $id_img = DB::table('inv_imagenes')->max('id_img') + 1;
        foreach ($request->file('imagenes') as $file) {
            $imageName = $etiqueta . '_' . uniqid();
            $path = $this->imageService->optimizeImageinv($file, $userFolder, $imageName);

            $fullUrl = asset('storage/' . $path);

            $img = new Inv_imagenes();
            $img->etiqueta = $etiqueta;
            $img->id_img = $id_img;
            $img->url_imagen = $fullUrl;
            $img->save();

            $paths[] = [
                'path' => $path,
                'url'  => $fullUrl,
            ];
        }

        return response()->json([
            'status'    => 'OK',
            'paths'     => $paths,
            'folderUrl' => asset('storage/' . $userFolder),
            'id_img'    => $id_img
        ], 201);
    }
    public function showData($id_inventario, $id_ciclo)
    {
        $sql = "
        SELECT 
            inv.*, 
            grupos.descripcion_grupo, 
            familias.descripcion_familia
        FROM inv_inventario AS inv
        LEFT JOIN dp_grupos AS grupos ON inv.id_grupo = grupos.id_grupo
        LEFT JOIN dp_familias AS familias ON inv.id_familia = familias.id_familia
        WHERE inv.id_inventario = ? 
          AND inv.id_ciclo = ?
    ";

        $data = DB::select($sql, [$id_inventario, $id_ciclo]);

        return response()->json($data, 200);
    }


    public function getFromServerToLocalDevice(int $ciclo, Request $request)
    {
        $request->merge(['ciclo_id'         => $ciclo]);



        $request->validate([
            'ciclo_id'          => 'required|integer|exists:inv_ciclos,idCiclo',
        ]);


        $from_id = $request->from_id ? $request->from_id : 0;



        $data = DB::select("SELECT *, 0 AS `offline` FROM inv_inventario WHERE id_ciclo = ? AND id_inventario > ? ", [
            $ciclo,
            $from_id
        ]);

        return response()->json(['status' => 'OK', 'data' => $data]);
    }



    /**
     * Store newly created resources in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeInventoryMultiple(int $ciclo, Request $request)
    {

        //validate zip file and items as json
        $request->validate([
            'items'   => 'required|json',
            'zipfile' => 'required|file|mimes:zip'
        ]);


        $cycleObj = InvCiclo::find($ciclo);

        if (!$cycleObj) {
            return response()->json([
                'status' => 'error',
                'code' => 404,
                'message' => 'No existe ciclo'
            ], 404);
        }


        //items to inventory
        $assets = [];

        //items with errors
        $errors = [];

        $images = [];

        $items = json_decode($request->items);

        foreach ($items as $key => $item) {


            $validator = Validator::make((array)$item, $this->rules());

            if ($validator->fails()) {

                $errors[] = ['index' => $key, 'etiqueta' => $item->etiqueta, 'errors' => $validator->errors()->get("*")];
            } else if (empty($errors)) {


                $activo = [

                    'id_grupo' => $item->id_grupo,
                    'id_familia' => $item->id_familia,
                    'descripcion_bien' => $item->descripcion_bien,
                    'descripcion_marca' => $item->descripcion_marca,
                    'idForma' => $item->idForma,
                    'idMaterial' => $item->idMaterial,
                    'etiqueta' => $item->etiqueta,
                    'modelo' => $item->modelo,
                    'serie' => $item->serie,
                    'capacidad' => $item->capacidad,
                    'estado' => $item->estado,
                    'color' => $item->color,
                    'tipo_trabajo' => $item->tipo_trabajo,
                    'carga_trabajo' => $item->carga_trabajo,
                    'estado_operacional' => $item->estado_operacional,
                    'estado_conservacion' => $item->estado_conservacion,
                    'condicion_Ambiental' => $item->condicion_Ambiental,
                    'cantidad_img' => $item->cantidad_img,
                    'id_img' => $item->id_img,
                    'id_ciclo' => $ciclo,
                    'idUbicacionN2' => $item->idUbicacionN2,
                    'codigoUbicacion_N1' => $item->codigoUbicacion_N1,
                    'responsable' => $item->responsable,
                    'latitud' => $item->latitud,
                    'longitud' => $item->longitud

                ];

                $assets[] = $activo;

                $images[] = [
                    'etiqueta' => $item->etiqueta,
                    'images' => $item->images
                ];
            }
        }

        if (!empty($errors)) {
            return response()->json([
                'status' => 'error',
                'message' => 'There are some items with errors, fix them and try again',
                'errors' => $errors
            ], 422);
        }






        $userFolder = "customers/" . $request->user()->nombre_cliente . "/images/inventario/temp/";


        $zip = new \ZipArchive;

        $open = $zip->open($request->file('zipfile')->getRealPath()) === TRUE;


        if ($open !== TRUE) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to open zip file'
            ], 400);
        }



        $extractPath = $userFolder . 'zip_' . time();


        if (!Storage::exists($extractPath)) {
            Storage::makeDirectory($extractPath);
        }

        $fullExtractPath = Storage::path($extractPath);

        $zip->extractTo($fullExtractPath);
        $zip->close();



        // Obtener todos los paths de los archivos extraídos
        $files = [];
        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($fullExtractPath));


        $customkey = 0;

        foreach ($rii as $file) {

            if ($file->isFile()) {


                $uploadedFile = new \Illuminate\Http\UploadedFile(
                    $file->getPathname(),
                    $file->getFilename(),
                    mime_content_type($file->getPathname()),
                    null,
                    true
                );


                $files[$customkey] = [
                    'file' => $uploadedFile,
                    'filename' => $file->getFilename(),
                    'etiquetas' =>  []
                ];

                foreach ($images as $key => $img) {
                    foreach ($img['images'] as $path) {
                        $filename = basename($path);
                        if ($filename == $file->getFilename()) {
                            $files[$customkey]['etiquetas'][] = $img['etiqueta'];
                        }
                    }
                }

                $customkey++;
            }
        }



        $failed = [];
        $saved = [];

        $imagesCollection = collect($images);

        foreach ($assets as $activo) {



            $existsInv = Inventario::where('etiqueta', '=', $activo['etiqueta'])->first();
            $existsCrud = CrudActivo::where('etiqueta', '=', $activo['etiqueta'])->first();

            if (!$existsInv && !$existsCrud) {
                $asset = Inventario::create($activo);
                $saved[] = $asset->etiqueta;


                $imgsAndTag = $imagesCollection->firstWhere('etiqueta', $asset->etiqueta);




                //Clonar Imágenes
                if (
                    $asset->id_img && $asset->id_img > 0 &&
                    (!$imgsAndTag['images'] || count($imgsAndTag['images']) === 0)
                ) {


                    $imagenes = DB::table('inv_imagenes')
                        ->where('id_img', $asset->id_img)
                        ->get();

                    $newIDImg = DB::table('inv_imagenes')->max('id_img') + 1;

                    //new id image
                    $asset->id_img = $newIDImg;

                    $asset->save();

                    foreach ($imagenes as $img) {
                        DB::table('inv_imagenes')->insert([
                            'id_img'     => $newIDImg,
                            'etiqueta'   => $asset->etiqueta,
                            'url_imagen' => $img->url_imagen,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            } else {
                $failed[] = $activo['etiqueta'];
            }
        }





        $id_img = DB::table('inv_imagenes')->max('id_img') + 1;

        $idsi = [];

        foreach ($files as $file) {

            if (count($file['etiquetas']) > 0) {

                foreach ($file['etiquetas'] as $etiqueta) {
                    if (!isset($idsi[$etiqueta])) {
                        $idsi[$etiqueta] = $id_img;
                        $id_img++;
                    }
                }
            }
        }

        $paths = [];

        foreach ($files as $file) {

            if (count($file['etiquetas']) > 0) {



                $userFolder = "customers/" . $request->user()->nombre_cliente . "/images/inventario/" . $file['etiquetas'][0] . "/" . now()->format('Y-m-d');

                // if (!Storage::exists($userFolder)) {
                //     Storage::makeDirectory($userFolder);
                // }

                $path = $this->imageService->optimizeImageinv($file['file'], $userFolder, $file['filename']);

                $paths[] = $path;

                $fullUrl = asset('storage/' . $path);



                foreach ($file['etiquetas'] as $etiqueta) {

                    $img = new Inv_imagenes();
                    $img->etiqueta = $etiqueta;
                    //ojo si es que existe otro proceso en paralelo
                    $img->id_img = $idsi[$etiqueta];
                    $img->url_imagen = $fullUrl;
                    $img->save();
                }
            }
        }

        // Eliminar todos los archivos antes de borrar el directorio
        if (Storage::exists($extractPath)) {
            $allFiles = Storage::allFiles($extractPath);
            foreach ($allFiles as $filePath) {
                Storage::delete($filePath);
            }
            Storage::deleteDirectory($extractPath);
        }


        return response()->json([
            'status' => 'OK',
            'message' => 'items created sucssessfuly',
            'data' => [
                'fails' => count($failed),
                'saved' => count($saved),
                'found_files' => count($paths),
                'failed_tags' => $failed
            ]
        ]);
    }






    protected function rules()
    {

        return [

            'id_grupo'              => 'required|integer',
            'id_familia'            => 'required|integer',
            'etiqueta'              => 'required|string',
            'id_ciclo'              => 'required|exists:inv_ciclos,idCiclo'

        ];
    }
}
