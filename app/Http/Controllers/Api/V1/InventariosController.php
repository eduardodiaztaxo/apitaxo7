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
            'codigoUbicacion'       => 'required|exists:ubicaciones_n2,codigoUbicacion'
        ]);
    
        $etiquetaInventario = DB::table('inv_inventario')->where('etiqueta', $request->etiqueta)->value('etiqueta');
        $etiquetaUnicaCrudActivo = DB::table('crud_activos')->where('etiqueta', $request->etiqueta)->value('etiqueta');
    
        if ($etiquetaInventario || $etiquetaUnicaCrudActivo) {
            return response('La etiqueta ya existe', 400);

        }

        $idUbicacionN2 = DB::table('ubicaciones_n2')->where('codigoUbicacion', $request->codigoUbicacion)->value('idUbicacionN2');
        $id_img = DB::selectOne("SELECT id_img FROM inv_imagenes WHERE etiqueta LIKE ?", [$request->etiqueta . '%']);
    
        $url_img = $id_img ? $id_img->id_img : null;
    
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
        $inventario->codigoUbicacion_N1  = strval(substr($request->codigoUbicacion, 0, 2)) . '';
        $inventario->responsable         = $this->getNombre();
        $inventario->save();
    
        return response()->json($inventario, 201);
    }
    

    public function configuracion($id_grupo, $modelo, $serie, $capacidad, $marcas, $etiqueta, $latitud, $longitud){
        $sql = "SELECT 
                    MAX(CASE WHEN id_atributo = $modelo THEN id_validacion END) AS conf_modelo,
                    MAX(CASE WHEN id_atributo = $serie THEN id_validacion END) AS conf_serie,
                    MAX(CASE WHEN id_atributo = $capacidad THEN id_validacion END) AS conf_capacidad,
                    MAX(CASE WHEN id_atributo = $marcas THEN id_validacion END) AS conf_marca,
                    MAX(CASE WHEN id_atributo = $etiqueta THEN valor_minimo END) AS lench_etiqueta,
                    MAX(CASE WHEN id_atributo = $etiqueta THEN tipo_etiqueta END) AS tipo_etiqueta,
                    MAX(CASE WHEN id_atributo = $latitud THEN id_validacion END) AS conf_latitud,
                    MAX(CASE WHEN id_atributo = $longitud THEN id_validacion END) AS conf_longitud
                FROM inv_atributos 
                WHERE id_atributo IN ($modelo, $serie, $capacidad, $marcas, $etiqueta, $latitud, $longitud) 
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
            $img->url_imagen = asset('storage/' . $path);
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