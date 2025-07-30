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
use App\Services\Imagenes\PictureSafinService;
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
            'idAgenda'              => 'required'
        ]);

        $existeEtiqueta = false;

        $idAgenda = $request->idAgenda;

        $Nivel3 = DB::table('ubicaciones_n3')->where('codigoUbicacion', $request->codigoUbicacion)->where('idAgenda', $idAgenda)->value('idUbicacionN3');

        if ($Nivel3 != null) {
            return $this->createinventarioNivel3($request, $Nivel3);
        }

        $etiquetaInventario = DB::table('inv_inventario')->where('etiqueta', $request->etiqueta)->value('etiqueta');
        $etiquetaUnicaCrudActivo = DB::table('crud_activos')->where('etiqueta', $request->etiqueta)->value('etiqueta');

        if ($etiquetaInventario || $etiquetaUnicaCrudActivo) {
            $existeEtiqueta = true;
        }

        if (!empty($request->etiqueta_padre)) {
            $etiquetaInventarioHijo = DB::table('inv_inventario')->where('etiqueta', $request->etiqueta_padre)->value('etiqueta');
            $etiquetaCrudActivoHijo = DB::table('crud_activos')->where('etiqueta', $request->etiqueta_padre)->value('etiqueta');

            if ($etiquetaInventarioHijo || $etiquetaCrudActivoHijo) {
                $existeEtiqueta = true;
            }
        }

        if ($existeEtiqueta) {
            return response('La etiqueta ya existe', 400);
        }


        if ($request->idUbicacionN2 > 0 && $request->codigoUbicacion_N1 > 0) {
            //Nivel2
            $codigoUbicacion_N1 = $request->codigoUbicacion_N1;
            $idUbicacionN2 = $request->idUbicacionN2;
            $codigoUbicacion_N2 = DB::table('ubicaciones_n2')
                ->where('idUbicacionN2',  $idUbicacionN2)
                ->value('codigoUbicacion');
        } else {

            $codigoUbicacion_N1 = null;
            if (!empty($request->codigoUbicacion)) {

                $codigoUbicacion_N1 = substr(strval($request->codigoUbicacion), 0, 2);
            }
            $idUbicacionN2 = DB::table('ubicaciones_n2')
                ->where('codigoUbicacion', $request->codigoUbicacion)
                ->where('idAgenda', $idAgenda)
                ->value('idUbicacionN2');
            $codigoUbicacion_N2 = $request->codigoUbicacion;
        }

        $idUbicacionGeo = $idAgenda;

        if ($request->cloneFichaDetalle == "true") {
            $imagenes = DB::table('inv_imagenes')
                ->where('id_img', $request->id_img_clone)
                ->get();

            $url_img = DB::table('inv_imagenes')->max('id_img') + 1;
            $origen = 'SAFIN_APP';
            $filename = '9999_' . $request->etiqueta;

            foreach ($imagenes as $img) {
                DB::table('inv_imagenes')->insert([
                    'id_img'     => $url_img,
                    'etiqueta'   => $request->etiqueta,
                    'origen'     => $origen,
                    'picture'    => $filename . '.jpg',
                    'url_imagen' => $img->url_imagen,
                    'url_picture' => $img->url_picture,
                    'created_at' => now()
                ]);
            }
        } else {

            $id_img = DB::table('inv_imagenes')
                ->where('etiqueta', $request->etiqueta)
                ->orderBy('id_img', 'desc')
                ->value('id_img');
            $url_img = $id_img ?? null;
        }

        if (intval($request->padre) === 1) {
            $etiquetaPadre = $request->etiqueta;
        } elseif (intval($request->padre) === 2) {
            $etiquetaPadre = $request->etiqueta_padre;
        }


        $inventario = new Inventario();
        $inventario->id_grupo            = $request->id_grupo;
        $inventario->id_familia          = $request->id_familia;
        $inventario->descripcion_bien    = $request->descripcion_bien;
        $inventario->id_bien             = intval($request->id_bien ?? 0);
        $inventario->descripcion_marca   = $request->descripcion_marca ?? '';
        $inventario->id_marca            = intval($request->id_marca ?? 0);
        $inventario->idForma             = intval($request->idForma ?? 0);
        $inventario->idMaterial          = intval($request->idMaterial ?? 0);
        $inventario->etiqueta            = $request->etiqueta;
        $inventario->modelo              = $request->modelo ?? '';
        $inventario->serie               = $request->serie ?? '';
        $inventario->latitud             = $request->latitud ?? 0;
        $inventario->longitud            = $request->longitud ?? 0;
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
        $inventario->idUbicacionGeo      = $idUbicacionGeo;
        $inventario->idUbicacionN2       = $idUbicacionN2;
        $inventario->codigoUbicacion_N2  = $codigoUbicacion_N2;
        $inventario->codigoUbicacion_N1  = $codigoUbicacion_N1;
        $inventario->idUbicacionN3       = 0;
        $inventario->codigoUbicacionN3   = 0;
        $inventario->responsable         = $this->getNombre();
        $inventario->idResponsable       = $this->getIdResponsable();
        $inventario->etiqueta_padre      = $etiquetaPadre ?? 'Sin Padre';
        /** edualejandro */
        $inventario->eficiencia          = $request->eficiencia ?? null;
        $inventario->texto_abierto_1     = $request->texto_abierto_1 ?? null;
        $inventario->texto_abierto_2     = $request->texto_abierto_2 ?? null;
        $inventario->texto_abierto_3     = $request->texto_abierto_3 ?? null;
        $inventario->texto_abierto_4     = $request->texto_abierto_4 ?? null;
        $inventario->texto_abierto_5     = $request->texto_abierto_5 ?? null;

        $inventario->save();

        return response()->json($inventario, 201);
    }




    public function  createinventarioNivel3(Request $request, $Nivel3)
    {
        $request->validate([
            'id_grupo'              => 'required|string',
            'id_familia'            => 'required|string',
            'etiqueta'              => 'required|string',
            'id_ciclo'              => 'required|exists:inv_ciclos,idCiclo',
            'codigoUbicacion'       => 'required',
        ]);

        $existeEtiqueta = false;

        $etiquetaInventario = DB::table('inv_inventario')->where('etiqueta', $request->etiqueta)->value('etiqueta');
        $etiquetaUnicaCrudActivo = DB::table('crud_activos')->where('etiqueta', $request->etiqueta)->value('etiqueta');

        if ($etiquetaInventario || $etiquetaUnicaCrudActivo) {
            $existeEtiqueta = true;
        }

        if (!empty($request->etiqueta_padre)) {
            $etiquetaInventarioHijo = DB::table('inv_inventario')->where('etiqueta', $request->etiqueta_padre)->value('etiqueta');
            $etiquetaCrudActivoHijo = DB::table('crud_activos')->where('etiqueta', $request->etiqueta_padre)->value('etiqueta');

            if ($etiquetaInventarioHijo || $etiquetaCrudActivoHijo) {
                $existeEtiqueta = true;
            }
        }

        if ($existeEtiqueta) {
            return response('La etiqueta ya existe', 400);
        }

        $idUbicacionN3 = $Nivel3;

        $idUbicacionGeo = DB::table('ubicaciones_n3')
            ->where('idUbicacionN3',  $Nivel3)
            ->value('idAgenda');

        if ($request->cloneFichaDetalle == "true") {
            $imagenes = DB::table('inv_imagenes')
                ->where('id_img', $request->id_img_clone)
                ->get();

            $url_img = DB::table('inv_imagenes')->max('id_img') + 1;
            $origen = 'SAFIN_APP';
            $filename = '9999_' . $request->etiqueta;

            foreach ($imagenes as $img) {
                DB::table('inv_imagenes')->insert([
                    'id_img'     => $url_img,
                    'etiqueta'   => $request->etiqueta,
                    'origen'     => $origen,
                    'picture'    => $filename . '.jpg',
                    'url_imagen' => $img->url_imagen,
                    'url_picture' => $img->url_picture,
                    'created_at' => now()
                ]);
            }
        } else {

            $id_img = DB::table('inv_imagenes')
                ->where('etiqueta', $request->etiqueta)
                ->orderBy('id_img', 'desc')
                ->value('id_img');
            $url_img = $id_img ?? null;
        }

        if (intval($request->padre) === 1) {
            $etiquetaPadre = $request->etiqueta;
        } elseif (intval($request->padre) === 2) {
            $etiquetaPadre = $request->etiqueta_padre;
        }

        $inventario = new Inventario();
        $inventario->id_grupo            = $request->id_grupo;
        $inventario->id_familia          = $request->id_familia;
        $inventario->descripcion_bien    = $request->descripcion_bien;
        $inventario->id_bien             = intval($request->id_bien ?? 0);
        $inventario->descripcion_marca   = $request->descripcion_marca ?? '';
        $inventario->id_marca            = intval($request->id_marca ?? 0);
        $inventario->idForma             = intval($request->idForma ?? 0);
        $inventario->idMaterial          = intval($request->idMaterial ?? 0);
        $inventario->etiqueta            = $request->etiqueta;
        $inventario->modelo              = $request->modelo ?? '';
        $inventario->serie               = $request->serie ?? '';
        $inventario->latitud             = $request->latitud ?? 0;
        $inventario->longitud            = $request->longitud ?? 0;
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
        $inventario->idUbicacionGeo      = $idUbicacionGeo;
        $inventario->idUbicacionN2       = 0;
        $inventario->codigoUbicacion_N2  = 0;
        $inventario->codigoUbicacion_N1  = 0;
        $inventario->idUbicacionN3       = $idUbicacionN3;
        $inventario->codigoUbicacionN4   = 0;
        $inventario->codigoUbicacionN3   = $request->codigoUbicacion;
        $inventario->responsable         = $this->getNombre();
        $inventario->idResponsable       = $this->getIdResponsable();
        $inventario->etiqueta_padre      = $etiquetaPadre ?? 'Sin Padre';
        /** edualejandro */
        $inventario->eficiencia          = $request->eficiencia ?? null;
        $inventario->texto_abierto_1     = $request->texto_abierto_1 ?? null;
        $inventario->texto_abierto_2     = $request->texto_abierto_2 ?? null;
        $inventario->texto_abierto_3     = $request->texto_abierto_3 ?? null;
        $inventario->texto_abierto_4     = $request->texto_abierto_4 ?? null;
        $inventario->texto_abierto_5     = $request->texto_abierto_5 ?? null;

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

        if (intval($request->padre) === 1) {
            $etiquetaPadre = $request->etiqueta;
        } elseif (intval($request->padre) === 2) {
            $etiquetaPadre = $request->etiqueta_padre;
        }

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
            'etiqueta_padre'      => $etiquetaPadre ?? 'Sin Padre',
            'update_inv'          => 0,
            /** edualejandro */
            'eficiencia'            => $request->eficiencia ?? null,
            'texto_abierto_1'       => $request->texto_abierto_1 ?? null,
            'texto_abierto_2'       => $request->texto_abierto_2 ?? null,
            'texto_abierto_3'       => $request->texto_abierto_3 ?? null,
            'texto_abierto_4'       => $request->texto_abierto_4 ?? null,
            'texto_abierto_5'       => $request->texto_abierto_5 ?? null,
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
                COALESCE(MAX(CASE WHEN id_atributo = 24 THEN id_validacion END), 0) AS conf_longitud,
                COALESCE(MAX(CASE WHEN id_atributo = 25 THEN id_validacion END), 0) AS conf_padre,

                /** edualejandro */
                COALESCE(MAX(CASE WHEN id_atributo = 26 THEN id_validacion END), 0) AS conf_eficiencia,
                COALESCE(MAX(CASE WHEN id_atributo = 26 THEN id_tipo_dato END), 0) AS tipo_dato_eficiencia,
                COALESCE(MAX(CASE WHEN id_atributo = 26 THEN valor_minimo END), 0) AS lench_Min_eficiencia,
                COALESCE(MAX(CASE WHEN id_atributo = 26 THEN valor_maximo END), 0) AS lench_Max_eficiencia,
                /** edualejandro */
                COALESCE(MAX(CASE WHEN id_atributo = 27 THEN id_validacion END), 0) AS conf_texto_abierto_1,
                COALESCE(MAX(CASE WHEN id_atributo = 27 THEN id_tipo_dato END), 0) AS tipo_dato_texto_abierto_1,
                COALESCE(MAX(CASE WHEN id_atributo = 27 THEN valor_minimo END), 0) AS lench_Min_texto_abierto_1,
                COALESCE(MAX(CASE WHEN id_atributo = 27 THEN valor_maximo END), 0) AS lench_Max_texto_abierto_1,
                COALESCE(
                    IFNULL(MAX(CASE WHEN id_atributo = 27 THEN label_input ELSE NULL END), 'Texto Abierto 1')
                ) AS label_texto_abierto_1,
                /** edualejandro */
                COALESCE(MAX(CASE WHEN id_atributo = 28 THEN id_validacion END), 0) AS conf_texto_abierto_2,
                COALESCE(MAX(CASE WHEN id_atributo = 28 THEN id_tipo_dato END), 0) AS tipo_dato_texto_abierto_2,
                COALESCE(MAX(CASE WHEN id_atributo = 28 THEN valor_minimo END), 0) AS lench_Min_texto_abierto_2,
                COALESCE(MAX(CASE WHEN id_atributo = 28 THEN valor_maximo END), 0) AS lench_Max_texto_abierto_2,
                COALESCE(
                    IFNULL(MAX(CASE WHEN id_atributo = 28 THEN label_input ELSE NULL END), 'Texto Abierto 2')
                ) AS label_texto_abierto_2,
                /** edualejandro */
                COALESCE(MAX(CASE WHEN id_atributo = 29 THEN id_validacion END), 0) AS conf_texto_abierto_3,
                COALESCE(MAX(CASE WHEN id_atributo = 29 THEN id_tipo_dato END), 0) AS tipo_dato_texto_abierto_3,
                COALESCE(MAX(CASE WHEN id_atributo = 29 THEN valor_minimo END), 0) AS lench_Min_texto_abierto_3,
                COALESCE(MAX(CASE WHEN id_atributo = 29 THEN valor_maximo END), 0) AS lench_Max_texto_abierto_3,
                COALESCE(
                    IFNULL(MAX(CASE WHEN id_atributo = 29 THEN label_input ELSE NULL END), 'Texto Abierto 3')
                ) AS label_texto_abierto_3,
                /** edualejandro */
                COALESCE(MAX(CASE WHEN id_atributo = 30 THEN id_validacion END), 0) AS conf_texto_abierto_4,
                COALESCE(MAX(CASE WHEN id_atributo = 30 THEN id_tipo_dato END), 0) AS tipo_dato_texto_abierto_4,
                COALESCE(MAX(CASE WHEN id_atributo = 30 THEN valor_minimo END), 0) AS lench_Min_texto_abierto_4,
                COALESCE(MAX(CASE WHEN id_atributo = 30 THEN valor_maximo END), 0) AS lench_Max_texto_abierto_4,
                COALESCE(
                    IFNULL(MAX(CASE WHEN id_atributo = 30 THEN label_input ELSE NULL END), 'Texto Abierto 4')
                ) AS label_texto_abierto_4,
                /** edualejandro */
                COALESCE(MAX(CASE WHEN id_atributo = 31 THEN id_validacion END), 0) AS conf_texto_abierto_5,
                COALESCE(MAX(CASE WHEN id_atributo = 31 THEN id_tipo_dato END), 0) AS tipo_dato_texto_abierto_5,
                COALESCE(MAX(CASE WHEN id_atributo = 31 THEN valor_minimo END), 0) AS lench_Min_texto_abierto_5,
                COALESCE(MAX(CASE WHEN id_atributo = 31 THEN valor_maximo END), 0) AS lench_Max_texto_abierto_5,
                COALESCE(
                    IFNULL(MAX(CASE WHEN id_atributo = 31 THEN label_input ELSE NULL END), 'Texto Abierto 5')
                ) AS label_texto_abierto_5

    
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

        $origen = 'SAFIN_APP';
        $id_img = DB::table('inv_imagenes')->max('id_img') + 1;
        $paths = [];

        foreach ($request->file('imagenes') as $index => $file) {
            $filename = '9999_' . $etiqueta . '_' . $index . '.jpg';

            $path = $file->storeAs(
                PictureSafinService::getImgSubdir($request->user()->nombre_cliente),
                $filename,
                'taxoImages'
            );

            $url = Storage::disk('taxoImages')->url($path);
            $url_pict = dirname($url) . '/';

            $img = new Inv_imagenes();
            $img->etiqueta = $etiqueta;
            $img->id_img = $id_img;
            $img->origen = $origen;
            $img->picture = $filename;
            $img->created_at = now();
            $img->url_imagen =  $url;
            $img->url_picture = $url_pict;
            $img->save();

            $paths[] = [
                'url' => $url_pict,
                'filename' => $filename
            ];
        }

        return response()->json([
            'status'    => 'OK',
            'paths'     => $paths,
            'folderUrl' => $url_pict,
            'id_img'    => $id_img - 1
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
                    'id_bien' => $item->id_bien,
                    'descripcion_marca' => $item->descripcion_marca,
                    'idForma' => $item->idForma,
                    'idMaterial' => $item->idMaterial,
                    'etiqueta' => $item->etiqueta,
                    'etiqueta_padre' => $item->etiqueta_padre,
                    'id_marca' => $item->id_marca,
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
                    'idUbicacionGeo' => $item->idUbicacionGeo,
                    'idUbicacionN2' => $item->idUbicacionN2,
                    'codigoUbicacion_N1' => $item->codigoUbicacion_N1,
                    'codigoUbicacionN3' => $item->codigoUbicacionN3,
                    'idUbicacionN3' => $item->idUbicacionN3,
                    'codigoUbicacionN4' => $item->codigoUbicacionN4,
                    'responsable' => $item->responsable,
                    'idResponsable' => $item->idResponsable,
                    'latitud' => $item->latitud,
                    'longitud' => $item->longitud,
                    'crud_activo_estado' => $item->crud_activo_estado,
                    'update_inv' => $item->update_inv,
                    /** falta campos */
                    /** edualejandro */
                    'eficiencia'            => $item->eficiencia ?? null,
                    'texto_abierto_1'       => $item->texto_abierto_1 ?? null,
                    'texto_abierto_2'       => $item->texto_abierto_2 ?? null,
                    'texto_abierto_3'       => $item->texto_abierto_3 ?? null,
                    'texto_abierto_4'       => $item->texto_abierto_4 ?? null,
                    'texto_abierto_5'       => $item->texto_abierto_5 ?? null,

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
                    $origen = 'SAFIN_APP';
                    $filename = '9999_' . $asset->etiqueta . '_' . uniqid() . '.jpg';

                    foreach ($imagenes as $img) {
                        DB::table('inv_imagenes')->insert([
                            'id_img'     => $newIDImg,
                            'origen'     => $origen,
                            'etiqueta'   => $asset->etiqueta,
                            'picture'    => $filename . '.jpg',
                            'url_imagen' => $img->url_imagen,
                            'url_picture'   => $img->url_picture,
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
        $origen = 'SAFIN_APP_OFFLINE';

        foreach ($files as $filekey => $file) {

            if (count($file['etiquetas']) > 0) {


                foreach ($file['etiquetas'] as $etiqueta) {

                    $filename = '9999_' . $etiqueta . '_' . $filekey . '.jpg';

                    $path = $file['file']->storeAs(
                        PictureSafinService::getImgSubdir($request->user()->nombre_cliente),
                        $filename,
                        'taxoImages'
                    );

                    $url = Storage::disk('taxoImages')->url($path);
                    $url_pict = dirname($url) . '/';

                    $img = new Inv_imagenes();
                    $img->etiqueta = $etiqueta;
                    $img->origen = $origen;
                    $img->picture = $filename;
                    //ojo si es que existe otro proceso en paralelo
                    $img->id_img = $idsi[$etiqueta];
                    $img->url_imagen = $url;
                    $img->url_picture = $url_pict;
                    $img->save();

                    $paths[] = $url;
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
                'failed_tags' => $failed,
                'image_urls' => $paths
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
