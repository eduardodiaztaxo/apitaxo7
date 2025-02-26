<?php

namespace App\Http\Controllers\Api\V1;
use Illuminate\Http\Request;
use App\Models\Inventario;
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
    public function createinventario(Request $request){
        $request->validate([
            'id_grupo'              => 'required|string',
            'id_familia'            => 'required|string',
            'etiqueta'              => 'required|string', 
            'id_ciclo'              => 'required|exists:inv_ciclos,idCiclo',
            'codigoUbicacion'       => 'required|exists:ubicaciones_n2,codigoUbicacion'
        ]);
    
        $idUbicacionN2 = DB::table('ubicaciones_n2')->where('codigoUbicacion', $request->codigoUbicacion)->value('idUbicacionN2');
        $id_img = DB::selectOne("SELECT id_img FROM inv_imagenes WHERE etiqueta = ?", [$request->etiqueta]);
    
        $url_img = $id_img ? $id_img->id_img : null;
    
        $inventario = new Inventario();
        $inventario->id_grupo            = $request->id_grupo;
        $inventario->id_familia          = $request->id_familia;
        $inventario->descripcion_bien    = $request->descripcion_bien;
        $inventario->descripcion_marca   = $request->descripcion_marca ?? '';
        $inventario->idForma             = intval($request->idForma ?? 0);
        $inventario->idMaterial          = intval($request->idMaterial ?? 0);
        $inventario->etiqueta            = $request->etiqueta;
        $inventario->modelo              = $request->modelo ?? '';
        $inventario->serie               = $request->serie ?? '';
        $inventario->capacidad           = intval($request->capacidad ?? 0);
        $inventario->estado              = intval($request->estado ?? 0);
        $inventario->color               = intval($request->color ?? 0);
        $inventario->tipo_trabajo        = intval($request->tipo_trabajo ?? 0);
        $inventario->carga_trabajo       = intval($request->carga_trabajo ?? 0);
        $inventario->estado_operacional  = intval($request->estado_operacional ?? 0);
        $inventario->estado_conservacion = intval($request->estado_conservacion ?? 0);
        $inventario->condicion_ambiental = intval($request->condicion_ambiental ?? 0);
        $inventario->cantidad_img        = $request->cantidad_img;
        $inventario->id_img              = $url_img;  
        $inventario->id_ciclo            = $request->id_ciclo;
        $inventario->codigoUbicacion     = $idUbicacionN2;
        $inventario->save();
    
        return response()->json($inventario, 201);
    }
    

    public function configuracion($id_grupo, $modelo, $serie, $capacidad, $marcas, $etiqueta){
        $sql = "SELECT 
                    MAX(CASE WHEN id_atributo = $modelo THEN id_validacion END) AS conf_modelo,
                    MAX(CASE WHEN id_atributo = $serie THEN id_validacion END) AS conf_serie,
                    MAX(CASE WHEN id_atributo = $capacidad THEN id_validacion END) AS conf_capacidad,
                    MAX(CASE WHEN id_atributo = $marcas THEN id_validacion END) AS conf_marca,
                    MAX(CASE WHEN id_atributo = $etiqueta THEN valor_minimo END) AS lench_etiqueta,
                    MAX(CASE WHEN id_atributo = $etiqueta THEN tipo_etiqueta END) AS tipo_etiqueta
                FROM inv_atributos 
                WHERE id_atributo IN ($modelo, $serie, $capacidad, $marcas, $etiqueta) 
                AND id_grupo = $id_grupo";
        $validacion = DB::select($sql);

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
    
        // Verificar si la carpeta ya está registrada en la base de datos
        $existingFolder = Inv_imagenes::where('etiqueta', $etiqueta)
            ->where('url_imagen', $userFolder)
            ->first();
    
        // Si no existe, la guardamos UNA SOLA VEZ
        if (!$existingFolder) {
            $img = new Inv_imagenes();
            $img->etiqueta = $etiqueta;
            $img->url_imagen = asset('storage/' . $userFolder);
            $img->save();
        }
    
        $paths = [];
    
        // Guardar imágenes en la carpeta, pero sin duplicar la carpeta en la BD
        foreach ($request->file('imagenes') as $file) {
            $imageName = $etiqueta . '_' . uniqid();
            $path = $this->imageService->optimizeImageinv($file, $userFolder, $imageName);
    
            $paths[] = [
                'path' => $path,
                'url'  => asset('storage/' . $path),
            ];
        }
    
        return response()->json([
            'status'    => 'OK',
            'paths'     => $paths,
            'folderUrl' => asset('storage/' . $userFolder)
        ], 201);
    }
}    