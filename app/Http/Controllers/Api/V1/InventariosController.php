<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Models\Inventario;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\Inv_imagenes;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\InventariosResource;
use App\Models\CrudActivo;
use App\Models\InvCiclo;
use App\Models\Responsable;
use App\Models\UbicacionGeografica;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;
use App\Services\ImageService;
use App\Services\Imagenes\PictureSafinService;
use App\Services\InvConfigService;
use App\Services\ActivoFinderService;
use App\Services\ProyectoUsuarioService;
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

        return $idResponsable ?? 0;
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

        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        $activoExiste = ActivoFinderService::findByEtiquetaAndCiclo($request->etiqueta, $request->id_ciclo);

        if ($activoExiste) {
            $existeEtiqueta = true;
        }

        if (!empty($request->etiqueta_padre)) {
            $etiquetaPadreExiste = ActivoFinderService::findByEtiquetaPadreAndCiclo($request->etiqueta_padre, $request->id_ciclo);

            if (!$etiquetaPadreExiste) {
                return response('La etiqueta padre ' . $request->etiqueta_padre . ' no existe', 400);
            }
        }

        if ($existeEtiqueta) {
            return response('La etiqueta ' . $request->etiqueta . ' ya existe', 400);
        }


        if (strlen($request->codigoUbicacion) === 2) {
            // Nivel 1
            $codigoUbicacion_N1 = $request->codigoUbicacion;
        } else if (strlen($request->codigoUbicacion) === 4) {
            // Nivel 2
            $codigoUbicacion_N2 = $request->codigoUbicacion;

            $idUbicacionN2 = DB::table('ubicaciones_n2')
                ->where('codigoUbicacion', $request->codigoUbicacion)
                ->where('idAgenda', $request->idAgenda)
                ->value('idUbicacionN2');
        } else {
            // Nivel 3
            $codigoUbicacionN3 = $request->codigoUbicacion;

            $idUbicacionN3 = DB::table('ubicaciones_n3')
                ->where('codigoUbicacion', $request->codigoUbicacion)
                ->where('idAgenda', $request->idAgenda)
                ->value('idUbicacionN3');
        }


        if ($request->clonarDesdeInventario == 'true' && intval($request->conf_fotos) === 0) {
            $imagenes = DB::table('inv_imagenes')
                ->where('id_img', $request->id_img_clone)
                ->where('id_proyecto', $id_proyecto)
                ->get();

            $url_img = DB::table('inv_imagenes')->where('id_proyecto', $id_proyecto)->max('id_img') + 1;
            $origen = 'SAFIN_CLONE';
            $filename = $id_proyecto . '_' . $request->etiqueta;

            foreach ($imagenes as $img) {
                DB::table('inv_imagenes')->insert([
                    'id_img'     => $url_img,
                    'etiqueta'   => $request->etiqueta,
                    'origen'     => $origen,
                    'picture'    => $filename . '.jpg',
                    'url_imagen' => $img->url_imagen,
                    'url_picture' => $img->url_picture,
                    'id_proyecto' => $id_proyecto,
                    'created_at' => now()
                ]);
            }
        }


        $id_img = DB::table('inv_imagenes')
            ->where('etiqueta', $request->etiqueta)
            ->orderBy('id_img', 'desc')
            ->value('id_img');
        $idImg = $id_img ?? null;

        if (intval($request->padre) === 1) {
            $etiquetaPadre = $request->etiqueta;
        } elseif (intval($request->padre) === 2) {
            $etiquetaPadre = $request->etiqueta_padre;
        }

        // If the responsible ID is provided in the request, use it; otherwise, get it from the authenticated user
        if ($request->idResponsable) {
            $getIdResponsable = $request->idResponsable;
            $responsable = Responsable::find($getIdResponsable)->name ?? null;
        } else {
            $getIdResponsable = $this->getIdResponsable();
            $responsable = $this->getNombre();
        }



        $ultimo_id_inventario = DB::table('inv_inventario')
            ->where('id_proyecto', $id_proyecto)
            ->max('id_inventario');

        $nuevo_id_inventario = ($ultimo_id_inventario ?? 0) + 1;

        $usuario = Auth::user()->name;

        $inventario = new Inventario();
        $inventario->id_inventario       = $nuevo_id_inventario;
        $inventario->id_proyecto         = $id_proyecto;
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
        $inventario->precision_geo       = $request->precision ?? 0;
        $inventario->calidad_geo         = $request->calidad ?? 0;
        $inventario->capacidad           = $request->capacidad ?? '';
        $inventario->estado              = intval($request->estado ?? 0);
        $inventario->color               = intval($request->color ?? 0);
        $inventario->tipo_trabajo        = intval($request->tipo_trabajo ?? 0);
        $inventario->carga_trabajo       = intval($request->carga_trabajo ?? 0);
        $inventario->estado_operacional  = intval($request->estado_operacional ?? 0);
        $inventario->estado_conservacion = intval($request->estado_conservacion ?? 0);
        $inventario->condicion_ambiental = intval($request->condicion_ambiental ?? 0);
        $inventario->cantidad_img        = $request->cantidad_img;
        $inventario->id_img              = $idImg;
        $inventario->id_ciclo            = $request->id_ciclo;
        $inventario->idUbicacionGeo      = $request->idAgenda;
        $inventario->idUbicacionN2       = $idUbicacionN2 ?? 0;
        $inventario->codigoUbicacion_N2  = $codigoUbicacion_N2 ?? 0;
        $inventario->codigoUbicacion_N1  = $codigoUbicacion_N1 ?? 0;
        $inventario->idUbicacionN3       = $idUbicacionN3 ?? 0;
        $inventario->responsable         = $responsable;
        $inventario->idResponsable       = $getIdResponsable;
        $inventario->codigoUbicacionN3   = $codigoUbicacionN3 ?? 0;
        $inventario->etiqueta_padre      = $etiquetaPadre ?? 'Sin Padre';
        /** edualejandro */
        $inventario->eficiencia          = $request->eficiencia ?? null;



        //texto_abierto_
        foreach (array_keys($request->all()) as $key) {
            $keyString = (string) $key;
            // Aquí puedes usar $keyString como necesites
            if (strpos($keyString, 'texto_abierto_') === 0) {
                $inventario->$keyString = $request->input($keyString) ?? null;
            }
        }


        $inventario->modo                = 'ONLINE';
        $inventario->creado_el           = date('Y-m-d H:i:s');
        $inventario->creado_por          = $usuario;

        $inventario->save();

        $inventario->fillCodeAndIDSEmplazamientos();

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

        $etiquetaOriginal = $request->etiqueta_original_editar;
        $etiquetaNueva = $request->etiqueta;

        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        $id_img = DB::table('inv_imagenes')
            ->where('etiqueta', $etiquetaOriginal)
            ->where('id_proyecto', $id_proyecto)
            ->orderBy('id_img', 'desc')
            ->value('id_img');

        $idImg = $id_img ?? null;


        if (intval($request->padre) === 1) {
            $etiquetaPadre = $request->etiqueta;
        } elseif (intval($request->padre) === 2) {
            $etiquetaPadre = $request->etiqueta_padre;
        }

        $estadoBien = $request->actualizarBien > 0 ? 3 : 0;
        $usuario = Auth::user()->name;

        // If the responsible ID is provided in the request, use it; otherwise, get it from the authenticated user
        if ($request->idResponsable) {
            $getIdResponsable = $request->idResponsable;
            $responsable = Responsable::find($getIdResponsable)->name ?? null;
        } else {
            $getIdResponsable = $this->getIdResponsable();
            $responsable = $this->getNombre();
        }

        $inv_arr = [
            'id_grupo'            => $request->id_grupo,
            'id_familia'          => $request->id_familia,
            'descripcion_bien'    => $request->descripcion_bien,
            'id_marca'            => intval($request->id_marca ?? 0),
            'descripcion_marca'   => $request->descripcion_marca,
            'modelo'              => $request->modelo,
            'serie'               => $request->serie,
            'idForma'             => intval($request->idForma ?? 0),
            'idMaterial'          => intval($request->idMaterial ?? 0),
            'latitud'             => $request->latitud ?? null,
            'longitud'            => $request->longitud ?? null,
            'precision_geo'       => $request->precision ?? null,
            'calidad_geo'         => $request->calidad ?? null,
            'capacidad'           => $request->capacidad,
            'estado'              => intval($request->estado ?? 0),
            'color'               => intval($request->color ?? 0),
            'tipo_trabajo'        => intval($request->tipo_trabajo ?? 0),
            'carga_trabajo'       => intval($request->carga_trabajo ?? 0),
            'estado_operacional'  => intval($request->estado_operacional ?? 0),
            'estado_conservacion' => intval($request->estado_conservacion ?? 0),
            'condicion_ambiental' => intval($request->condicion_ambiental ?? 0),
            'cantidad_img'        => $request->cantidad_img,
            'id_img'              => $idImg,
            'etiqueta_padre'      => $etiquetaPadre ?? 'Sin Padre',
            'update_inv'          => 0,
            'eficiencia'          => $request->eficiencia ?? null,
            'crud_activo_estado'  => $estadoBien,
            'modo'                => 'ONLINE',
            'modificado_el'       => date('Y-m-d H:i:s'),
            'modificado_por'      => $usuario,
            'etiqueta'            => $etiquetaNueva,
            'idResponsable'       => $getIdResponsable,
            'responsable'         => $responsable
        ];

        foreach (array_keys($request->all()) as $key) {
            if (strpos((string)$key, 'texto_abierto_') === 0) {
                $inv_arr[$key] = $request->input($key) ?? null;
            }
        }

        Inventario::where('etiqueta', $etiquetaOriginal)->where('id_proyecto', $id_proyecto)->update($inv_arr);

        if ($etiquetaOriginal !== $etiquetaNueva) {
            DB::table('inv_imagenes')
                ->where('etiqueta', $etiquetaOriginal)
                ->where('id_proyecto', $id_proyecto)
                ->update([
                    'etiqueta'   => $etiquetaNueva,
                    'origen'     => 'SAFIN_APP_ETIQUETA_EDITADA',
                    'updated_at' => now()
                ]);
        } else {
            DB::table('inv_imagenes')
                ->where('etiqueta', $etiquetaOriginal)
                ->where('id_proyecto', $id_proyecto)
                ->update([
                    'origen'     => 'SAFIN_APP_INVENTARIO_ACTUALIZADO',
                    'updated_at' => now()
                ]);
        }

        $inventarioActualizado = Inventario::where('etiqueta', $etiquetaNueva)->where('id_proyecto', $id_proyecto)->first();


        if (intval($request->padre) === 1) {
            $etiquetaPadre = $request->etiqueta;
        } elseif (intval($request->padre) === 2) {
            $etiquetaPadre = $request->etiqueta_padre;
        }

        $estadoBien = $request->actualizarBien > 0 ? 3 : 0;
        $usuario = Auth::user()->name;

        $inv_arr = [
            'id_grupo'            => $request->id_grupo,
            'id_familia'          => $request->id_familia,
            'descripcion_bien'    => $request->descripcion_bien,
            'id_marca'            => $request->id_marca ?? 0,
            'descripcion_marca'   => $request->descripcion_marca,
            'modelo'              => $request->modelo,
            'serie'               => $request->serie,
            'idForma'             => intval($request->idForma ?? null),
            'idMaterial'          => intval($request->idMaterial ?? null),
            'latitud'             => $request->latitud ?? null,
            'longitud'            => $request->longitud ?? null,
            'precision_geo'       => $request->precision ?? null,
            'calidad_geo'         => $request->calidad ?? null,
            'capacidad'           => $request->capacidad,
            'estado'              => intval($request->estado ?? null),
            'color'               => intval($request->color ?? null),
            'tipo_trabajo'        => intval($request->tipo_trabajo ?? null),
            'carga_trabajo'       => intval($request->carga_trabajo ?? null),
            'estado_operacional'  => intval($request->estado_operacional ?? null),
            'estado_conservacion' => intval($request->estado_conservacion ?? null),
            'condicion_ambiental' => intval($request->condicion_ambiental ?? null),
            'cantidad_img'        => $request->cantidad_img,
            'id_img'              => $idImg,
            'etiqueta_padre'      => $etiquetaPadre ?? 'Sin Padre',
            'update_inv'          => 0,
            'eficiencia'          => $request->eficiencia ?? null,
            'crud_activo_estado'  => $estadoBien,
            'modo'                => 'ONLINE',
            'modificado_el'       => date('Y-m-d H:i:s'),
            'modificado_por'      => $usuario,
            'etiqueta'            => $etiquetaNueva,
        ];

        foreach (array_keys($request->all()) as $key) {
            if (strpos((string)$key, 'texto_abierto_') === 0) {
                $inv_arr[$key] = $request->input($key) ?? null;
            }
        }

        Inventario::where('etiqueta', $etiquetaOriginal)->update($inv_arr);

        if ($etiquetaOriginal !== $etiquetaNueva) {
            DB::table('inv_imagenes')
                ->where('etiqueta', $etiquetaOriginal)
                ->update([
                    'etiqueta'   => $etiquetaNueva,
                    'origen'     => 'SAFIN_APP_ETIQUETA_EDITADA',
                    'updated_at' => now()
                ]);
        } else {
            DB::table('inv_imagenes')
                ->where('etiqueta', $etiquetaOriginal)
                ->update([
                    'origen'     => 'SAFIN_APP_ACTUALIZADO',
                    'updated_at' => now()
                ]);
        }

        $inventarioActualizado = Inventario::where('etiqueta', $etiquetaNueva)->first();

        if ($inventarioActualizado) {
            $inventarioActualizado->fillCodeAndIDSEmplazamientos();
        }

        return response()->json([
            'message'    => 'Inventario actualizado con éxito',
            'inventario' => $inventarioActualizado
        ], 200);
    }

    public function updateAdjustCoordinatesInventory(Request $request, $ciclo, $etiqueta)
    {
        $validator = Validator::make($request->all(), [
            'adjusted_lat' => 'required|numeric',
            'adjusted_lng' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid input',
                'errors'  => $validator->errors()
            ], 422);
        }

        $invObj = Inventario::where('etiqueta', $etiqueta)->first();

        if (!$invObj) {
            return response()->json([
                'status' => 'error',
                'message' => 'Not Found'
            ], 404);
        }

        $invObj->adjusted_lat = $request->adjusted_lat;
        $invObj->adjusted_lng = $request->adjusted_lng;

        $invObj->save();

        return response()->json([
            'status' => 'OK',
            'message' => 'Updated successfully'
        ]);
    }

    public function updateAdjustCoordinatesInventoryDebugData(Request $request, $ciclo, $etiqueta)
    {
        $validator = Validator::make($request->all(), [
            'adjusted_lat' => 'required|numeric',
            'adjusted_lng' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid input',
                'errors'  => $validator->errors()
            ], 422);
        }

        $invObj = Inventario::where('etiqueta', $etiqueta)->first();

        if (!$invObj) {
            return response()->json([
                'status' => 'error',
                'message' => 'Not Found'
            ], 404);
        }

        $invObj->adjusted_lat = $request->adjusted_lat;
        $invObj->adjusted_lng = $request->adjusted_lng;

        $invObj->fix_quality = $request->fix_quality;
        $invObj->satellites = $request->satellites;
        $invObj->sd_lat = $request->sd_lat;
        $invObj->sd_lon = $request->sd_lon;

        $invObj->save();

        return response()->json([
            'status' => 'OK',
            'message' => 'Updated successfully'
        ]);
    }

    public function getImagesByEtiqueta($etiqueta, $cycleid, $idActivo)
    {
        if (empty($cycleid) && !empty($idActivo)) {
            $crudImagenes = DB::select("
                SELECT 
                    id_foto as idLista,
                    id_foto as id_img,
                    '' as etiqueta,
                    CONCAT(url_picture, picture) as url_imagen
                FROM crud_activos_pictures
                WHERE id_activo = ?
            ", [$idActivo]);

            return response()->json($crudImagenes);
        }

        $ciclo = InvCiclo::find($cycleid);

        if (!$ciclo) {
            return response()->json([]);
        }

        if ($ciclo->idTipoCiclo == ActivoFinderService::TIPO_INVENTARIO) {
            $invImagenes = DB::select("
                SELECT 
                    idLista,
                    id_img,
                    etiqueta,
                    url_imagen
                FROM inv_imagenes
                WHERE etiqueta = ?
                AND id_proyecto = ?
            ", [$etiqueta, $ciclo->id_proyecto]);

            return response()->json($invImagenes);
        }

        $idActivoCrud = ActivoFinderService::getIdByEtiquetaAndCiclo($etiqueta, $cycleid);

        if (!$idActivoCrud) {
            return response()->json([]);
        }

        $crudImagenes = DB::select("
            SELECT 
                id_foto as idLista,
                id_foto as id_img,
                ? as etiqueta,
                CONCAT(url_picture, picture) as url_imagen
            FROM crud_activos_pictures
            WHERE id_activo = ?
        ", [$etiqueta, $idActivoCrud]);

        return response()->json($crudImagenes);
    }

    // se le pasa el ciclo
    public function deleteImageByEtiqueta($etiqueta, $id_img, $idLista, $idActivo = null, $cycleid)
    {
        // Si viene idActivo directamente, eliminar de crud_activos_pictures
        if (!empty($idActivo)) {
            $imagen = DB::table('crud_activos_pictures')
                ->where('id_activo', $idActivo)
                ->where('id_foto', $idLista)
                ->first();

            if (!$imagen) {
                return response()->json([
                    'status'  => 'ERROR',
                    'message' => 'Imagen no encontrada en crud_activos_pictures.'
                ], 404);
            }



            ImageService::deleteImageInMainOrSecondDisk(Auth::user()->nombre_cliente, $imagen->picture);

            DB::table('crud_activos_pictures')
                ->where('id_foto', $imagen->id_foto)
                ->delete();

            return response()->json([
                'status'  => 'OK',
                'message' => 'Imagen eliminada con éxito de crud_activos_pictures.'
            ], 200);
        }

        // Obtener el ciclo para determinar el tipo
        $ciclo = InvCiclo::find($cycleid);

        if (!$ciclo) {
            return response()->json([
                'status'  => 'ERROR',
                'message' => 'Ciclo no encontrado.'
            ], 404);
        }

        // Si es ciclo tipo 1 (inventario), eliminar de inv_imagenes
        if ($ciclo->idTipoCiclo == ActivoFinderService::TIPO_INVENTARIO) {
            $imagen = Inv_imagenes::where('etiqueta', $etiqueta)
                ->where('id_img', $id_img)
                ->where('idLista', $idLista)
                ->where('id_proyecto', $ciclo->id_proyecto)
                ->first();

            if (!$imagen) {
                return response()->json([
                    'status'  => 'ERROR',
                    'message' => 'Imagen no encontrada en inventario.'
                ], 404);
            }

            ImageService::deleteImageInMainOrSecondDisk(Auth::user()->nombre_cliente, $imagen->picture);

            $imagen->delete();

            return response()->json([
                'status'  => 'OK',
                'message' => 'Imagen eliminada con éxito del inventario.'
            ], 200);
        }

        // Si es ciclo tipo 2 (auditoría), eliminar de crud_activos_pictures
        $idActivoCrud = ActivoFinderService::getIdByEtiquetaAndCiclo($etiqueta, $cycleid);

        if (!$idActivoCrud) {
            return response()->json([
                'status'  => 'ERROR',
                'message' => 'Activo no encontrado.'
            ], 404);
        }

        $imagenCrud = DB::table('crud_activos_pictures')
            ->where('id_activo', $idActivoCrud)
            ->where('id_foto', $idLista)
            ->first();

        if (!$imagenCrud) {
            return response()->json([
                'status'  => 'ERROR',
                'message' => 'Imagen no encontrada en crud_activos_pictures.'
            ], 404);
        }

        $filePath = PictureSafinService::getImgSubdir(Auth::user()->nombre_cliente) . '/' . $imagenCrud->picture;
        if (Storage::disk('taxoImages')->exists($filePath)) {
            Storage::disk('taxoImages')->delete($filePath);
        }
        ImageService::deleteImageInMainOrSecondDisk(Auth::user()->nombre_cliente, $imagen->picture);

        DB::table('crud_activos_pictures')
            ->where('id_foto', $imagenCrud->id_foto)
            ->delete();

        return response()->json([
            'status'  => 'OK',
            'message' => 'Imagen eliminada con éxito de crud_activos_pictures.'
        ], 200);
    }

    public function nombreInputs()
    {
        $inputs = DB::select("
        SELECT 
            t.descripcion,
            iv.id_atributo,
            iv.label_input
        FROM inv_atributos as iv
        INNER JOIN inv_tipos_atributos as t
        WHERE iv.id_atributo IN (27,28,29,30,31)
        AND iv.id_atributo = t.id_atributo
    ");

        return response()->json([
            'status' => 'success',
            'data' => $inputs
        ]);
    }


    public function configuracion($id_grupo, $cycleid)
    {

        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

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
                COALESCE(MAX(CASE WHEN id_atributo = 79 THEN id_validacion END), 0) AS conf_responsable


            FROM inv_atributos 
            WHERE id_grupo = ?
            AND id_proyecto = ?";

        $validacion = DB::select($sql, [$id_grupo, $id_proyecto]);

        $inputs_map = InvConfigService::getOpenedTextConfigInput((int)$id_grupo, (int)$id_proyecto);

        $validacion[0]->custom_fields = $inputs_map;

        return response()->json($validacion, 200);
    }


    //recibe el ciclo y la etiqueta
    public function ImageByEtiqueta(Request $request, $etiqueta)
    {
        $request->validate([
            'imagenes.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240'
        ]);

        if ($request->conf_fotos != 0 && strtolower($request->tipo) == 'true') {
            $origen = 'SAFIN_CLONE';
        } else {
            $origen = 'SAFIN_APP';
        }

        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        $existeEtiqueta = false;
        $activoExiste = ActivoFinderService::findByEtiquetaAndCiclo($request->etiqueta, $request->id_ciclo);

        if ($activoExiste) {
            $existeEtiqueta = true;
        }

        if (!empty($request->etiqueta_padre)) {
            $etiquetaPadreExiste = ActivoFinderService::findByEtiquetaPadreAndCiclo($request->etiqueta_padre, $request->id_ciclo);

            if (!$etiquetaPadreExiste) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'La etiqueta padre ' . $request->etiqueta_padre . ' no existe.',
                ], 400);
            }
        }

        if ($existeEtiqueta && $request->update_inv == 'false') {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'La etiqueta ' . $etiqueta . ' ingresada ya existe.',
            ], 400);
        }


        $maxId = DB::table('inv_imagenes')->where('id_proyecto', $id_proyecto)->max('id_img');
        $id_img = $maxId !== null ? $maxId + 1 : 1;

        $paths = [];

        foreach ($request->file('imagenes') as $index => $file) {

            $filename = $id_proyecto . '_' . $etiqueta . '_' . $index . '.jpg';

            $url = ImageService::saveImageInMainOrSecondDisk($file, $request->user()->nombre_cliente, $filename);
            $url_pict = dirname($url) . '/';

            $img = new Inv_imagenes();
            $img->id_proyecto = $id_proyecto;
            $img->etiqueta = $etiqueta;
            $img->id_img = $id_img;
            $img->origen = $origen;
            $img->picture = $filename;
            $img->created_at = now();
            $img->url_imagen = $url;
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
            'id_img'    => $id_img,
            'name'      => $origen
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
        $request->merge(['ciclo_id' => $ciclo]);



        $request->validate([
            'ciclo_id' => 'required|integer|exists:inv_ciclos,idCiclo',
        ]);


        $from_id = $request->from_id ? $request->from_id : 0;



        $data = DB::select("SELECT *, 0 AS `offline` FROM inv_inventario WHERE id_ciclo = ? AND id_inventario > ? ", [
            $ciclo,
            $from_id
        ]);

        return response()->json(['status' => 'OK', 'data' => $data]);
    }


    public function addImageByEtiqueta(Request $request, $etiqueta)
    {
        $request->validate([
            'imagenes.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        try {
            $origen = 'SAFIN_APP_IMG_NEW';
            $cycleId = $request->cycle_id ?? null;

            // Buscar activo usando ActivoFinderService
            $activoExiste = ActivoFinderService::findByEtiquetaAndCiclo($etiqueta, $cycleId);

            if (!$activoExiste) {
                return response()->json([
                    'status'  => 'ERROR',
                    'message' => 'La etiqueta ' . $etiqueta . ' no existe.',
                ], 404);
            }

            // Determinar si es crud_activo basado en el tipo de modelo retornado
            $esCrudActivo = $activoExiste instanceof \App\Models\CrudActivo;

            $id_img = $request->id_img ?? null;
            $paths = [];

            $idProyecto = ProyectoUsuarioService::getIdProyecto();

            if ($esCrudActivo) {
                // Obtener idActivo: puede venir del request (scan de bienes) o del modelo
                $idActivo = $request->idActivo ?? $activoExiste->idActivo;

                $maxNumero = DB::table('crud_activos_pictures')
                    ->where('id_activo', $idActivo)
                    ->selectRaw("MAX(CAST(SUBSTRING_INDEX(picture, '_', -1) AS UNSIGNED)) as max_num")
                    ->value('max_num');

                $contador = $maxNumero ?? 0;
            } else {
                $maxNumero = DB::table('inv_imagenes')
                    ->where('etiqueta', $etiqueta)
                    ->where('id_proyecto', $idProyecto)
                    ->selectRaw("MAX(CAST(REPLACE(SUBSTRING_INDEX(picture, '_', -1), '.jpg', '') AS UNSIGNED)) as max_num")
                    ->value('max_num');

                $contador = $maxNumero ?? 0;
            }

            foreach ($request->file('imagenes') as $file) {
                $contador++;

                $filename = $idProyecto . '_' . $etiqueta . '_' . $contador . '.jpg';

                $url = ImageService::saveImageInMainOrSecondDisk($file, $request->user()->nombre_cliente, $filename);

                $url_pict = dirname($url) . '/';

                if ($esCrudActivo) {
                    DB::table('crud_activos_pictures')->insert([
                        'id_activo'    => $idActivo,
                        'picture'      => $filename,
                        'origen'       => $origen,
                        'url_picture'  => $url_pict,
                        'url_imagen'   => $url,
                        'fecha_update' => now(),
                        'idProyecto'   => $idProyecto,
                    ]);
                } else {
                    $img = new Inv_imagenes();
                    $img->etiqueta     = $etiqueta;
                    $img->id_img       = $id_img;
                    $img->origen       = $origen;
                    $img->picture      = $filename;
                    $img->created_at   = now();
                    $img->updated_at   = now();
                    $img->url_imagen   = $url;
                    $img->url_picture  = $url_pict;
                    $img->id_proyecto  = $idProyecto;
                    $img->save();
                }

                $paths[] = [
                    'id_img'   => $contador,
                    'url'      => $url,
                    'filename' => $filename,
                ];
            }

            return response()->json([
                'status'    => 'OK',
                'message'   => 'Imágenes agregadas correctamente',
                'paths'     => $paths,
                'folderUrl' => $paths[0]['url'] ?? null,
                'origen'    => $origen,
                'tipo'      => $esCrudActivo ? 'crud_activo' : 'inventario',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'ERROR',
                'message' => 'Error al agregar imágenes',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store newly created resources in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeInventoryMultiple(int $ciclo, Request $request)
    {
        $this->validateRequest($request);

        $cycleObj = InvCiclo::find($ciclo);
        if (!$cycleObj) {
            return $this->jsonError('No existe ciclo', 404);
        }

        $usuario = Auth::user()->name;

        // Procesar direcciones
        $idMapaGeo = $this->procesarDirecciones($request->direcciones, $ciclo, $usuario);

        // Procesar emplazamientos N1/N2/N3
        $idMapaN1_Codigo = $this->procesarUbicacionesN1($request->emplazamientoN1, $ciclo, $usuario, $idMapaGeo);
        [$mapaIdN2, $mapaCodN2] = $this->procesarUbicacionesN2($request->emplazamientoN2, $ciclo, $usuario, $idMapaGeo);
        [$mapaIdN3, $mapaCodN3] = $this->procesarUbicacionesN3($request->emplazamientoN3, $ciclo, $usuario, $idMapaGeo);

        //Procesar bienes y marcas
        $mapaIdListaBienes = $this->procesarBienes($request->bienes, $ciclo);
        $mapaIdListaMarcas = $this->procesarMarcas($request->marcas, $ciclo);

        // Procesar items
        [$assets, $errors, $images] = $this->procesarItems(
            $request->items,
            $ciclo,
            $usuario,
            $idMapaGeo,
            $idMapaN1_Codigo,
            $mapaIdN2,
            $mapaCodN2,
            $mapaIdN3,
            $mapaCodN3,
            $mapaIdListaBienes,
            $mapaIdListaMarcas
        );

        if (!empty($errors)) {
            return $this->jsonError('Hay errores en algunos items', 422, ['errors' => $errors]);
        }

        //Procesar ZIP
        $files = $this->procesarZipImagenes($request, $images);

        //Guardar activos e imágenes
        [$saved, $failed, $paths] = $this->guardarActivosConImagenes($assets, $files, $request->user()->nombre_cliente, $ciclo);

        return response()->json([
            'status' => 'OK',
            'message' => 'Inventario procesado correctamente',
            'data' => [
                'items' => $saved,
                'fails' => count($failed),
                'saved' => count($saved),
                'found_files' => count($paths),
                'failed_tags' => $failed,
                'image_urls' => $paths
            ]
        ]);
    }

    private function validateRequest(Request $request)
    {
        $request->validate([
            'items'             => 'nullable|json',
            'zipfile'           => 'nullable|file|mimes:zip',
            'bienes'            => 'nullable|json',
            'marcas'            => 'nullable|json',
            'emplazamientoN1'   => 'nullable|json',
            'emplazamientoN2'   => 'nullable|json',
            'emplazamientoN3'   => 'nullable|json',
            'direcciones'       => 'nullable|json'
        ]);
    }

    private function jsonError(string $message, int $code, array $extra = [])
    {
        return response()->json(array_merge([
            'status'  => 'error',
            'code'    => $code,
            'message' => $message
        ], $extra), $code);
    }

    private function obtenerIdAgendaActualizado($idAgendaOffline, array $mapa)
    {
        return $mapa[$idAgendaOffline] ?? $idAgendaOffline;
    }

    private function obtenerCodigoActualizado($codigoOffline, array $mapa)
    {
        return $mapa[$codigoOffline] ?? $codigoOffline;
    }

    private function procesarItems($itemsJson, int $ciclo, string $usuario, $idMapaGeo, $idMapaN1_Codigo, $mapaIdN2, $mapaCodN2, $mapaIdN3, $mapaCodN3, $mapaIdListaBienes, $mapaIdListaMarcas)
    {
        $items = $itemsJson ? json_decode($itemsJson) : [];
        $assets = [];
        $errors = [];
        $images = [];

        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        $ultimo_id_inventario = DB::table('inv_inventario')
            ->where('id_proyecto', $id_proyecto)
            ->max('id_inventario');

        $siguiente_id_inventario = ($ultimo_id_inventario ?? 0) + 1;

        foreach ($items as $key => $item) {
            $validator = Validator::make((array) $item, $this->rules());
            if ($validator->fails()) {
                $errors[] = ['index' => $key, 'etiqueta' => $item->etiqueta, 'errors' => $validator->errors()->get("*")];
                continue;
            }

            $activo = $this->mapActivo($item, $ciclo, $usuario, $idMapaGeo, $idMapaN1_Codigo, $mapaIdN2, $mapaCodN2, $mapaIdN3, $mapaCodN3, $mapaIdListaBienes, $mapaIdListaMarcas, $siguiente_id_inventario);
            $assets[] = $activo;
            $images[] = ['etiqueta' => $item->etiqueta, 'images' => $item->images];

            // Incrementar para el siguiente item
            $siguiente_id_inventario++;
        }

        return [$assets, $errors, $images];
    }

    /**
     * Mapear un item
     */
    private function mapActivo($item, $ciclo, $usuario, $idMapaGeo, $idMapaN1_Codigo, $mapaIdN2, $mapaCodN2, $mapaIdN3, $mapaCodN3, $mapaIdListaBienes, $mapaIdListaMarcas, $id_inventario)
    {
        // Determinar si se usa
        $usarMapas = isset($idMapaGeo[$item->idUbicacionGeo]);
        $id_bien_final  = $mapaIdListaBienes[$item->id_bien] ?? $item->id_bien;
        $id_marca_final = $mapaIdListaMarcas[$item->id_marca] ?? $item->id_marca;
        $getIdResponsable = $this->getIdResponsable();
        $responsable = $this->getNombre();

        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        return [
            'id_inventario'      => $id_inventario,
            'id_proyecto'        => $id_proyecto,
            'id_grupo'           => $item->id_grupo,
            'id_familia'         => $item->id_familia,
            'descripcion_bien'   => $item->descripcion_bien,
            'id_bien'            => $id_bien_final,
            'descripcion_marca'  => $item->descripcion_marca,
            'id_marca'           => $id_marca_final,
            'idForma'            => $item->idForma,
            'idMaterial'         => $item->idMaterial,
            'etiqueta'           => $item->etiqueta,
            'etiqueta_padre'     => $item->etiqueta_padre,
            'modelo'             => $item->modelo,
            'serie'              => $item->serie,
            'capacidad'          => $item->capacidad,
            'estado'             => $item->estado,
            'color'              => $item->color,
            'tipo_trabajo'       => $item->tipo_trabajo,
            'carga_trabajo'      => $item->carga_trabajo,
            'estado_operacional' => $item->estado_operacional,
            'estado_conservacion' => $item->estado_conservacion,
            'condicion_Ambiental' => $item->condicion_Ambiental,
            'cantidad_img'       => $item->cantidad_img,
            'id_img'             => $item->id_img,
            'id_ciclo'           => $ciclo,
            'idUbicacionGeo'     => $item->idUbicacionGeo,
            'codigoUbicacion_N1' => $idMapaN1_Codigo[$item->codigoUbicacion_N1] ?? $item->codigoUbicacion_N1,
            'idUbicacionN2'      => $usarMapas ? ($mapaIdN2[$item->codigoUbicacion_N2] ?? null) : $item->idUbicacionN2,
            'codigoUbicacion_N2' => $usarMapas ? ($mapaCodN2[$item->codigoUbicacion_N2] ?? null) : $item->codigoUbicacion_N2,
            'idUbicacionN3'      => $usarMapas ? ($mapaIdN3[$item->codigoUbicacionN3] ?? null) : $item->idUbicacionN3,
            'codigoUbicacionN3'  => $usarMapas ? ($mapaCodN3[$item->codigoUbicacionN3] ?? null) : $item->codigoUbicacionN3,
            'responsable'        => $responsable,
            'idResponsable'      => $getIdResponsable,
            'latitud'            => $item->latitud,
            'longitud'           => $item->longitud,
            'crud_activo_estado' => $item->crud_activo_estado,
            'update_inv'         => $item->update_inv,
            'eficiencia'         => $item->eficiencia ?? null,
            /**edualejandro */
            'texto_abierto_1'    => $item->texto_abierto_1 ?? null,
            'texto_abierto_2'    => $item->texto_abierto_2 ?? null,
            'texto_abierto_3'    => $item->texto_abierto_3 ?? null,
            'texto_abierto_4'    => $item->texto_abierto_4 ?? null,
            'texto_abierto_5'    => $item->texto_abierto_5 ?? null,
            'texto_abierto_6'    => $item->texto_abierto_6 ?? null,
            'texto_abierto_7'    => $item->texto_abierto_7 ?? null,
            'texto_abierto_8'    => $item->texto_abierto_8 ?? null,
            'texto_abierto_9'    => $item->texto_abierto_9 ?? null,
            'texto_abierto_10'   => $item->texto_abierto_10 ?? null,
            'modo'               => 'OFFLINE',
            'creado_el'          => $item->crud_activo_estado != 3 ? now() : null,
            'creado_por'         => $item->crud_activo_estado != 3 ? $usuario : null,
            'modificado_el'      => $item->crud_activo_estado == 3 ? now() : null,
            'modificado_por'     => $item->crud_activo_estado == 3 ? $usuario : null
        ];
    }

    /**
     * Procesar direcciones 
     */
    private function procesarDirecciones($direccionesJson, int $ciclo, string $usuario): array
    {
        $direcciones = $direccionesJson ? json_decode($direccionesJson) : [];
        $idMapaGeo = [];

        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        foreach ($direcciones as $d) {
            $idRegion = DB::table('regiones')->where('descripcion', $d->region)->value('idRegion');
            $idComuna = DB::table('comunas')->where('descripcion', $d->comuna)->value('idComuna');

            $existeUbicacion = DB::table('ubicaciones_geograficas')
                ->where([
                    ['descripcion', '=', $d->descripcion],
                    ['idProyecto', '=', $id_proyecto],
                    ['zona', '=', $d->zona],
                    ['region', '=', $idRegion],
                    ['comuna', '=', $idComuna],
                    ['direccion', '=', $d->direccion]
                ])->value('idUbicacionGeo');

            $idUbicacionInsertada = $existeUbicacion ?: DB::table('ubicaciones_geograficas')->insertGetId([
                'idProyecto'    => $id_proyecto,
                'codigoCliente' => $d->codigoCliente,
                'descripcion'   => $d->descripcion,
                'zona'          => $d->zona,
                'region'        => $idRegion,
                'comuna'        => $idComuna,
                'direccion'     => $d->direccion,
                'idPunto'       => $d->idPunto,
                'estadoGeo'     => $d->estadoGeo,
                'newApp'        => $d->newApp,
                'modo'          => $d->modo
            ]);

            $idMapaGeo[$d->idUbicacionGeo] = $idUbicacionInsertada;

            DB::table('inv_ciclos_puntos')->updateOrInsert(
                ['idCiclo' => $ciclo, 'idPunto' => $idUbicacionInsertada],
                ['usuario' => $usuario, 'fechaCreacion' => now(), 'id_estado' => 2, 'auditoria_general' => 0, 'modo' => 'OFFLINE']
            );
        }

        return $idMapaGeo;
    }

    /**
     * Procesar Ubicaciones N1
     */
    private function procesarUbicacionesN1($json, int $ciclo, string $usuario, array $idMapaGeo): array
    {
        $data = $json ? json_decode($json) : [];
        $mapaCodigo = [];

        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        foreach ($data as $n1) {
            $idAgendaReal = $this->obtenerIdAgendaActualizado($n1->idAgenda, $idMapaGeo);

            $codigoExistente = DB::table('ubicaciones_n1')
                ->where('idProyecto', $id_proyecto)
                ->where('idAgenda', $idAgendaReal)
                ->max('codigoUbicacion');

            $nuevoCodigo = $codigoExistente ? str_pad(((int) $codigoExistente) + 1, 2, '0', STR_PAD_LEFT) : $n1->codigoUbicacion;

            $registro = DB::table('ubicaciones_n1')
                ->where('idAgenda', $idAgendaReal)
                ->where('idProyecto', $id_proyecto)
                ->where('descripcionUbicacion', $n1->nombre)
                ->first();

            $codigoReal = $registro->codigoUbicacion ?? $nuevoCodigo;

            if (!$registro) {
                DB::table('ubicaciones_n1')->insert([
                    'idProyecto'           => $id_proyecto,
                    'idAgenda'             => $idAgendaReal,
                    'codigoUbicacion'      => $nuevoCodigo,
                    'descripcionUbicacion' => $n1->nombre,
                    'estado'               => 1,
                    'fechaCreacion'        => now(),
                    'usuario'              => $usuario,
                    'newApp'               => $n1->newApp,
                    'modo'                 => $n1->modo
                ]);
            }

            $mapaCodigo[$n1->codigoUbicacion] = $codigoReal;
        }

        return $mapaCodigo;
    }

    /**
     * Procesar Ubicaciones N2
     */
    private function procesarUbicacionesN2($json, int $ciclo, string $usuario, array $idMapaGeo): array
    {
        $data = $json ? json_decode($json) : [];
        $mapaId = [];
        $mapaCodigo = [];

        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        foreach ($data as $n2) {
            $idAgendaReal = $this->obtenerIdAgendaActualizado($n2->idAgenda, $idMapaGeo);

            $existeCodigo = DB::table('ubicaciones_n2')
                ->where('idAgenda', $idAgendaReal)
                ->where('idProyecto', $id_proyecto)
                ->where('codigoUbicacion', $n2->codigoUbicacion)
                ->exists();

            if ($existeCodigo) {

                $prefijo = substr($n2->codigoUbicacion, 0, 2);

                $codigoExistente = DB::table('ubicaciones_n2')
                    ->where('idAgenda', $idAgendaReal)
                    ->where('idProyecto', $id_proyecto)
                    ->where('codigoUbicacion', 'like', $prefijo . '%')
                    ->selectRaw("MAX(CAST(codigoUbicacion AS UNSIGNED)) as maximo")
                    ->value('maximo');

                $nuevoCodigo = str_pad($codigoExistente + 1, 4, '0', STR_PAD_LEFT);
            } else {
                $nuevoCodigo = $n2->codigoUbicacion;
            }

            $registro = DB::table('ubicaciones_n2')
                ->where('idAgenda', $idAgendaReal)
                ->where('idProyecto', $id_proyecto)
                ->where('descripcionUbicacion', $n2->nombre)
                ->first();

            if (!$registro) {
                $idInsertado = DB::table('ubicaciones_n2')->insertGetId([
                    'idProyecto'           => $id_proyecto,
                    'idAgenda'             => $idAgendaReal,
                    'codigoUbicacion'      => $nuevoCodigo,
                    'descripcionUbicacion' => $n2->nombre,
                    'estado'               => 1,
                    'fechaCreacion'        => now(),
                    'usuario'              => $usuario,
                    'newApp'               => $n2->newApp,
                    'modo'                 => $n2->modo
                ]);
                $mapaId[$nuevoCodigo] = $idInsertado;
                $mapaCodigo[$nuevoCodigo] = $nuevoCodigo;
            } else {
                $mapaId[$registro->codigoUbicacion] = $registro->idUbicacionN2;
                $mapaCodigo[$registro->codigoUbicacion] = $registro->codigoUbicacion;
            }
        }

        return [$mapaId, $mapaCodigo];
    }

    /**
     * Procesar Ubicaciones N3
     */
    private function procesarUbicacionesN3($json, int $ciclo, string $usuario, array $idMapaGeo): array
    {
        $data = $json ? json_decode($json) : [];
        $mapaId = [];
        $mapaCodigo = [];
        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        foreach ($data as $n3) {
            $idAgendaReal = $this->obtenerIdAgendaActualizado($n3->idAgenda, $idMapaGeo);

            $existeCodigo = DB::table('ubicaciones_n3')
                ->where('idAgenda', $idAgendaReal)
                ->where('idProyecto', $id_proyecto)
                ->where('codigoUbicacion', $n3->codigoUbicacion)
                ->exists();

            if ($existeCodigo) {

                $prefijo = substr($n3->codigoUbicacion, 0, 2);

                $codigoExistente = DB::table('ubicaciones_n3')
                    ->where('idAgenda', $idAgendaReal)
                    ->where('idProyecto', $id_proyecto)
                    ->where('codigoUbicacion', 'like', $prefijo . '%')
                    ->selectRaw("MAX(CAST(codigoUbicacion AS UNSIGNED)) as maximo")
                    ->value('maximo');

                $nuevoCodigo = str_pad($codigoExistente + 1, 6, '0', STR_PAD_LEFT);
            } else {
                $nuevoCodigo = $n3->codigoUbicacion;
            }

            $registro = DB::table('ubicaciones_n3')
                ->where('idAgenda', $idAgendaReal)
                ->where('idProyecto', $id_proyecto)
                ->where('descripcionUbicacion', $n3->nombre)
                ->first();

            if (!$registro) {
                $idInsertado = DB::table('ubicaciones_n3')->insertGetId([
                    'idProyecto'           => $id_proyecto,
                    'idAgenda'             => $idAgendaReal,
                    'codigoUbicacion'      => $nuevoCodigo,
                    'descripcionUbicacion' => $n3->nombre,
                    'estado'               => 1,
                    'fechaCreacion'        => now(),
                    'usuario'              => $usuario,
                    'newApp'               => $n3->newApp,
                    'modo'                 => $n3->modo
                ]);
                $mapaId[$nuevoCodigo] = $idInsertado;
                $mapaCodigo[$nuevoCodigo] = $nuevoCodigo;
            } else {
                $mapaId[$registro->codigoUbicacion] = $registro->idUbicacionN3;
                $mapaCodigo[$registro->codigoUbicacion] = $registro->codigoUbicacion;
            }
        }

        return [$mapaId, $mapaCodigo];
    }

    /**
     * Procesar bienes Nuevos
     */

    private function procesarBienes($json, int $ciclo): array
    {
        $bienes = $json ? json_decode($json) : [];
        $mapaIdListaBienes = [];

        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        foreach ($bienes as $bien) {
            $existeBien = DB::table('inv_bienes_nuevos')
                ->where('descripcion', $bien->descripcion)
                ->where('idAtributo', $bien->idAtributo)
                ->where('idProyecto', $id_proyecto)
                ->where('id_familia', $bien->id_familia)
                ->first();

            if (!$existeBien) {
                $maxListaIndicelista = DB::table('indices_listas')
                    ->where('idAtributo', $bien->idAtributo)
                    ->where('idProyecto', $id_proyecto)
                    ->where('idIndice', $bien->id_familia)
                    ->max('idLista');

                $maxListaBienes = DB::table('inv_bienes_nuevos')
                    ->where('idAtributo', $bien->idAtributo)
                    ->where('idProyecto', $id_proyecto)
                    ->where('id_familia', $bien->id_familia)
                    ->max('idLista');

                $newIdLista = max($maxListaIndicelista ?? 0, $maxListaBienes ?? 0) + 1;


                DB::table('inv_bienes_nuevos')->insert([
                    'idLista'          => $newIdLista,
                    'idIndice'         => $bien->idIndice,
                    'idProyecto'       => $id_proyecto,
                    'descripcion'      => $bien->descripcion,
                    'observacion'      => $bien->observacion,
                    'idAtributo'       => $bien->idAtributo,
                    'id_familia'       => $bien->id_familia,
                    'id_grupo'         => $bien->id_grupo,
                    'ciclo_inventario' => $bien->ciclo_inventario,
                    'creadoPor'        => $bien->creadoPor,
                    'fechaCreacion'    => $bien->fechaCreacion,
                    'modo'             => $bien->modo
                ]);

                $mapaIdListaBienes[$bien->idLista] = $newIdLista;
            } else {
                $mapaIdListaBienes[$bien->idLista] = $existeBien->idLista;
            }
        }

        return $mapaIdListaBienes;
    }

    /**
     * Procesar marcas Nuevas
     */
    private function procesarMarcas($json, int $ciclo): array
    {
        $marcas = $json ? json_decode($json) : [];
        $mapaIdListaMarcas = [];
        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        foreach ($marcas as $marca) {
            $existeMarca = DB::table('inv_marcas_nuevos')
                ->where('descripcion', $marca->descripcion)
                ->where('idAtributo', $marca->idAtributo)
                ->where('idProyecto', $id_proyecto)
                ->where('id_familia', $marca->id_familia)
                ->first();

            if (!$existeMarca) {
                $maxListaIndicelista = DB::table('indices_listas')
                    ->where('idAtributo', $marca->idAtributo)
                    ->where('idProyecto', $id_proyecto)
                    ->where('idIndice', $marca->id_familia)
                    ->max('idLista');

                $maxListaMarcas = DB::table('inv_marcas_nuevos')
                    ->where('idAtributo', $marca->idAtributo)
                    ->where('idProyecto', $id_proyecto)
                    ->where('id_familia', $marca->id_familia)
                    ->max('idLista');

                $newIdLista = max($maxListaIndicelista ?? 0, $maxListaMarcas ?? 0) + 1;


                DB::table('inv_marcas_nuevos')->insert([
                    'idLista'          => $newIdLista,
                    'idIndice'         => $marca->idIndice,
                    'idProyecto'       => $id_proyecto,
                    'descripcion'      => $marca->descripcion,
                    'observacion'      => $marca->observacion,
                    'idAtributo'       => $marca->idAtributo,
                    'id_familia'       => $marca->id_familia,
                    'ciclo_inventario' => $marca->ciclo_inventario,
                    'creadoPor'        => $marca->creadoPor,
                    'fechaCreacion'    => $marca->fechaCreacion,
                    'modo'             => $marca->modo
                ]);

                $mapaIdListaMarcas[$marca->idLista] = $newIdLista;
            } else {
                $mapaIdListaMarcas[$marca->idLista] = $existeMarca->idLista;
            }
        }

        return $mapaIdListaMarcas;
    }

    /**
     * Procesar ZIP
     */
    private function procesarZipImagenes(Request $request, array $images): array
    {
        $userFolder = "customers/" . $request->user()->nombre_cliente . "/images/inventario/temp/";
        $files = [];

        if (!$request->hasFile('zipfile')) return $files;

        $zip = new \ZipArchive;
        if ($zip->open($request->file('zipfile')->getRealPath()) !== TRUE) return $files;

        $extractPath = $userFolder . 'zip_' . time();
        if (!Storage::exists($extractPath)) Storage::makeDirectory($extractPath);

        $fullExtractPath = Storage::path($extractPath);
        $zip->extractTo($fullExtractPath);
        $zip->close();

        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($fullExtractPath));
        $customkey = 0;

        foreach ($rii as $file) {
            if (!$file->isFile()) continue;

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
                'etiquetas' => []
            ];

            foreach ($images as $img) {
                foreach ($img['images'] as $path) {
                    if (basename($path) == $file->getFilename()) {
                        $files[$customkey]['etiquetas'][] = $img['etiqueta'];
                    }
                }
            }
            $customkey++;
        }

        return $files;
    }
    /**
     * Guardar Img
     */
    private function guardarActivosConImagenes(array $assets, array $files, string $cliente, int $ciclo)
    {
        $saved = [];
        $failed = [];
        $paths = [];

        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        $id_img = DB::table('inv_imagenes')->where('id_proyecto', $id_proyecto)->max('id_img') + 1;
        $idsi = [];


        foreach ($files as $file) {
            foreach ($file['etiquetas'] as $etiqueta) {
                if (!isset($idsi[$etiqueta])) {
                    $idsi[$etiqueta] = $id_img;
                    $id_img++;
                }
            }
        }


        foreach ($assets as &$activo) {
            if (isset($idsi[$activo['etiqueta']])) {
                $activo['id_img'] = $idsi[$activo['etiqueta']];
            } else {

                $activo['id_img'] = $activo['id_img'] ?? 1;
            }

            [$existsInv, $existsCrud] = ActivoFinderService::checkExistsByEtiquetaAndCiclo($activo['etiqueta'], $ciclo);

            if (!$existsInv && !$existsCrud) {
                $asset = Inventario::create($activo);
                $asset->fillCodeAndIDSEmplazamientos();
                $saved[] = $asset->etiqueta;
            } elseif ($activo['crud_activo_estado'] == 3 && $existsInv) {
                $existsInv->update($activo);
                $saved[] = $activo['etiqueta'];
            } else {
                $failed[] = $activo['etiqueta'];
            }
        }

        $origen = 'SAFIN_APP_OFFLINE';


        foreach ($files as $filekey => $file) {
            foreach ($file['etiquetas'] as $etiqueta) {
                $activo = collect($assets)->firstWhere('etiqueta', $etiqueta);
                if ($activo && $activo['crud_activo_estado'] == 3) continue;

                $filename = $id_proyecto . '_' . $etiqueta . '_' . $filekey . '.jpg';

                $url = ImageService::saveImageInMainOrSecondDisk($file['file'], $cliente, $filename);
                $url_pict = dirname($url) . '/';

                $img = new Inv_imagenes();
                $img->id_proyecto = $id_proyecto;
                $img->etiqueta = $etiqueta;
                $img->origen = $origen;
                $img->picture = $filename;
                $img->id_img = $idsi[$etiqueta];
                $img->url_imagen = $url;
                $img->url_picture = $url_pict;
                $img->save();

                $paths[] = $url;
            }
        }

        foreach ($files as $file) {
            Storage::delete($file['file']->getPathname());
        }

        return [$saved, $failed, $paths];
    }

    public function rangoPermitido($idAgenda)
    {
        $ubicacion = UbicacionGeografica::find($idAgenda);

        if (!$ubicacion) {
            return response()->json([], 404);
        }

        $puntos = $ubicacion->verificacion_range($idAgenda);

        return response()->json($puntos);
    }


    /**
     * Display the specified resource.
     *
     * @param int  $cycle
     * @param int  $etiqueta
     * @return \Illuminate\Http\Response
     */
    public function showByEtiqueta(int $cycle, string $etiqueta)
    {

        $cicloObj = InvCiclo::find($cycle);

        if (!$cicloObj) {
            return response()->json([
                'status' => 'NOK',
                'message' => 'Ciclo no encontrado',
                'code' => 404
            ], 404);
        }

        //
        $activo = Inventario::where('etiqueta', '=', $etiqueta)->first();

        if (!$activo) {
            return response()->json([
                "message" => "Not Found",
                "status"  => "error"
            ], 404);
        }

        $resource = new InventariosResource($activo);
        return response()->json($resource, 200);
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
