<?php

namespace App\Http\Controllers\Api\V1;
use Illuminate\Http\Request;
use App\Models\Inventario;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\Inv_imagenes;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;
use App\Services\ImageService;
use Illuminate\Support\Facades\Storage;


class InventariosController extends Controller
{
    private $imageService;

    public function __construct( ImageService $imageService)
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
    public function createinventario(Request $request){
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

        if($request->idUbicacionN2 > 0 && $request->codigoUbicacion_N1 > 0){
            $codigoUbicacion_N1 = $request->codigoUbicacion_N1;
            $idUbicacionN2 = $request->idUbicacionN2;
        }else{
            $codigoUbicacion_N1 = null;
            if (!empty($request->codigoUbicacion)) {
                $codigoUbicacion_N1 = substr(strval($request->codigoUbicacion), 0, 2);
            }
            $idUbicacionN2 = DB::table('ubicaciones_n2')
            ->where('codigoUbicacion', $request->codigoUbicacion)
             ->value('idUbicacionN2');
        }
           
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
        }else{

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
        $inventario->descripcion_marca   = $request->descripcion_marca ?? 'null';
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
        $inventario->codigoUbicacion     = $idUbicacionN2;
        $inventario->codigoUbicacion_N1  = $codigoUbicacion_N1;
        $inventario->responsable         = $this->getNombre();
        $inventario->save();
    
        return response()->json($inventario, 201);
    }
    

   public function configuracion($id_grupo) {
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


   
    public function ImageByEtiqueta(Request $request, $etiqueta) {
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


}    