<?php

namespace App\Http\Controllers\Api\V1\Comunes;

use App\Http\Controllers\Controller;
use App\Models\IndiceLista;
use App\Models\Inventario_bienes;
use App\Models\Inventario_marcas;
use App\Models\IndiceLista13;
use App\Models\Responsable;
use Illuminate\Http\Request;
use App\Models\Grupo;
use App\Models\Familia;
use App\Models\IndiceListaColores;
use App\Models\IndiceListaOperacional;
use App\Models\IndiceListaTipoTrabajo;
use App\Models\IndiceListaCargaTrabajo;
use App\Models\IndiceListaConservacion;
use App\Models\IndiceListaCondicionAmbiental;
use App\Models\IndiceListaMaterial;
use App\Models\IndiceListaForma;
use App\Models\IndiceListaEstado;
use App\Models\Inv_ciclos_categorias;
use Illuminate\Support\Facades\Auth;
use App\Models\InvCiclo;
use Illuminate\Support\Facades\DB;
use App\Services\ActivoFinderService;
use App\Services\ProyectoUsuarioService;


class DatosActivosController extends Controller
{
    //
    public function marcas()
    {
        $collection = IndiceLista::all();
        return response()->json($collection, 200);
    }

    public function responsables()
    {
        $collection = Responsable::all();
        return response()->json($collection, 200);
    }



    public function estados($idCiclo = null)
    {
        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        if (!$id_proyecto && $idCiclo) {
            $id_proyecto = DB::table('inv_ciclos')->where('idCiclo', $idCiclo)->value('id_proyecto');
        }

        if (!$id_proyecto) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontró proyecto para el usuario'
            ], 404);
        }

        $collection = IndiceListaEstado::where('idProyecto', $id_proyecto)
            ->get();

        return response()->json($collection, 200);
    }


    public function grupo($ciclo)
    {
        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        if (!$id_proyecto) {
            $id_proyecto = DB::table('inv_ciclos')->where('idCiclo', $ciclo)->value('id_proyecto');
        }

        $idsGrupos = DB::select("
                SELECT 
                    dp_grupos.descripcion_grupo,
                    dp_familias.descripcion_familia,
                    dp_familias.id_grupo,
                    dp_familias.id_familia
                FROM dp_grupos
                INNER JOIN dp_familias 
                    ON dp_grupos.id_grupo = dp_familias.id_grupo
                LEFT JOIN inv_ciclos_categorias 
                    ON inv_ciclos_categorias.id_familia = dp_familias.id_familia
                AND inv_ciclos_categorias.id_grupo = dp_familias.id_grupo
                AND inv_ciclos_categorias.idCiclo = ?
                AND inv_ciclos_categorias.id_proyecto = ?
                WHERE IFNULL(inv_ciclos_categorias.id_familia, '0') <> '0'
                ORDER BY dp_grupos.descripcion_grupo, dp_familias.descripcion_familia
            ", [$ciclo, $id_proyecto]);

        $ids = collect($idsGrupos)->pluck('id_grupo')->unique()->values()->toArray();

        $grupos = Grupo::whereIn('id_grupo', $ids)->get();

        return response()->json($grupos, 200);
    }


    public function familia($codigo_grupo, $ciclo)
    {
        $idsFamilia = DB::select("
                SELECT 
                    dp_grupos.descripcion_grupo,
                    dp_familias.descripcion_familia,
                    dp_familias.id_grupo,
                    dp_familias.id_familia
                FROM dp_grupos
                INNER JOIN dp_familias 
                    ON dp_grupos.id_grupo = dp_familias.id_grupo
                LEFT JOIN inv_ciclos_categorias 
                    ON inv_ciclos_categorias.id_familia = dp_familias.id_familia
                AND inv_ciclos_categorias.id_grupo = dp_familias.id_grupo
                AND inv_ciclos_categorias.idCiclo = ?
                AND inv_ciclos_categorias.id_grupo = ?
                WHERE IFNULL(inv_ciclos_categorias.id_familia, '0') <> '0'
                ORDER BY dp_grupos.descripcion_grupo, dp_familias.descripcion_familia
            ", [$ciclo, $codigo_grupo]);

        $ids = collect($idsFamilia)->pluck('id_familia')->unique()->values()->toArray();

        $collection = Familia::where('id_grupo', $codigo_grupo)->whereIn('id_familia', $ids)->get();

        return response()->json($collection, 200);
    }



    public function bienesGrupoFamilia($idCiclo)
    {
        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        if (!$id_proyecto) {
            $id_proyecto = DB::table('inv_ciclos')->where('idCiclo', $idCiclo)->value('id_proyecto') ?? 0;
        }

        $familias = DB::select("
        SELECT 
            dp_grupos.descripcion_grupo,
            dp_familias.descripcion_familia,
            dp_familias.id_grupo,
            dp_familias.id_familia
        FROM dp_grupos
        INNER JOIN dp_familias 
            ON dp_grupos.id_grupo = dp_familias.id_grupo
        LEFT JOIN inv_ciclos_categorias 
            ON inv_ciclos_categorias.id_familia = dp_familias.id_familia
           AND inv_ciclos_categorias.id_grupo = dp_familias.id_grupo
           AND inv_ciclos_categorias.idCiclo = ?
           AND inv_ciclos_categorias.id_proyecto = ?
        WHERE IFNULL(inv_ciclos_categorias.id_familia, '0') <> '0'
        ORDER BY dp_grupos.descripcion_grupo, dp_familias.descripcion_familia
    ", [$idCiclo, $id_proyecto]);

        // Convertimos a colección para facilitar manipulación
        $familias = collect($familias);

        // Paso 2: Extraer IDs de familia
        $idsFamilia = $familias->pluck('id_familia')->all();

        if (empty($idsFamilia)) {
            return response()->json([], 200);
        }

        // Paso 3: Consulta bienes
        $in = implode(',', $idsFamilia);
        $bienesRaw = DB::select("
       SELECT * FROM (
    SELECT 
        idLista,
        idAtributo,
        idIndice,
        idProyecto,
        descripcion,
        observacion,
        listaRapida,
        creadoPor,
        modificadoPor,
        fechaCreacion,
        fechaModificacion,
        estado,
        foto,
        id_familia,
        ciclo_inventario,
        modo
    FROM inv_bienes_nuevos
    WHERE idAtributo = 1 AND id_familia IN ($in) AND idProyecto = ?

    UNION ALL

    SELECT 
        idLista,
        idAtributo,
        idIndice,
        idProyecto,
        descripcion,
        observacion,
        listaRapida,
        creadoPor,
        modificadoPor,
        fechaCreacion,
        fechaModificacion,
        estado,
        foto,
        id_familia,
        ciclo_inventario,
        'ONLINE' as modo
    FROM indices_listas
    WHERE idAtributo = 1 AND id_familia IN ($in) AND idProyecto = ?
) AS resultado;
 ", [$id_proyecto, $id_proyecto]);

        // Paso 4: Enriquecer los bienes con info de grupo y familia
        $bienes = collect($bienesRaw)
            ->map(function ($bien) use ($familias) {
                $familia = $familias->firstWhere('id_familia', $bien->id_familia);
                if ($familia) {
                    $bien->descripcion_familia = $familia->descripcion_familia;
                    $bien->descripcion_grupo = $familia->descripcion_grupo;
                    $bien->id_grupo = $familia->id_grupo;
                }
                return $bien;
            });

        return response()->json($bienes->values(), 200);
    }


    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $cycle_id
     * @return \Illuminate\Http\Response
     */
    public function showAllByBienesGrupoFamilia(Request $request, int $cycle_id)
    {

        $objCycle = InvCiclo::find($cycle_id);

        if (!$objCycle) {
            return response()->json([
                'message' => 'Ciclo no encontrado'
            ], 404);
        }

        $complete_word = trim($request->keyword);

        $possible_name_words = keyword_search_terms_from_keyword($request->keyword);

        $queryBuilderbienesGrupoFamilia = $objCycle->bienesGrupoFamiliaByCycle();

        if (
            !!keyword_is_searcheable($request->keyword)
        ) {

            $queryBuilderbienesGrupoFamilia = $queryBuilderbienesGrupoFamilia
                ->where(function ($query) use ($complete_word) {
                    $query->where('descripcion', 'LIKE', "%$complete_word%");
                    $query->orWhere('descripcion_grupo', 'LIKE', "%$complete_word%");
                    $query->orWhere('descripcion_familia', 'LIKE', "%$complete_word%");
                });

            if (count($possible_name_words) > 1) {

                $queryBuilderbienesGrupoFamilia = $queryBuilderbienesGrupoFamilia->orWhere(function ($query) use ($possible_name_words) {
                    foreach ($possible_name_words as $palabra) {
                        $query->where('descripcion', 'LIKE', "%$palabra%");
                    }
                });

                $queryBuilderbienesGrupoFamilia = $queryBuilderbienesGrupoFamilia->orWhere(function ($query) use ($possible_name_words) {
                    foreach ($possible_name_words as $palabra) {
                        $query->where('descripcion_grupo', 'LIKE', "%$palabra%");
                    }
                });

                $queryBuilderbienesGrupoFamilia = $queryBuilderbienesGrupoFamilia->orWhere(function ($query) use ($possible_name_words) {
                    foreach ($possible_name_words as $palabra) {
                        $query->where('descripcion_familia', 'LIKE', "%$palabra%");
                    }
                });
            }
        }

        $queryBuilderbienesGrupoFamilia = $queryBuilderbienesGrupoFamilia->orderBy('descripcion')->orderBy('descripcion_grupo')->orderBy('descripcion_familia');


        if ($request->from && $request->rows) {
            $offset = $request->from - 1;
            $limit = $request->rows;
            $queryBuilderbienesGrupoFamilia->offset($offset)->limit($limit);
        }


        $bienesGrupoFamilia = $queryBuilderbienesGrupoFamilia->get();

        return response()->json($bienesGrupoFamilia, 200);
    }


    public function countAllByBienesGrupoFamilia(Request $request, int $cycle_id)
    {
        $objCycle = InvCiclo::find($cycle_id);

        if (!$objCycle) {
            return response()->json([
                'message' => 'Ciclo no encontrado'
            ], 404);
        }

        $countBienesGrupoFamilia = $objCycle->bienesGrupoFamiliaByCycle()->count();

        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'data' => ['count' => $countBienesGrupoFamilia]
        ], 200);
    }



    public function buscarGrupoFamilia($id_familia)
    {
        $sql = "
          SELECT descripcion_familia, id_grupo FROM dp_familias WHERE id_familia = $id_familia
        ";

        $result = DB::select($sql);

        $id_grupo = $result[0]->id_grupo;

        $sql = "
          SELECT * FROM dp_grupos WHERE id_grupo = $id_grupo
        ";

        $grupo = DB::select($sql);

        return response()->json([
            'grupo' => $grupo[0]
        ], 200);
    }
    public function bienes_Marcas($id_familia, $idCiclo = null)
    {
        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        if (!$id_proyecto && $idCiclo) {
            $id_proyecto = DB::table('inv_ciclos')->where('idCiclo', $idCiclo)->value('id_proyecto');
        }

        if (empty($id_proyecto)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontró proyecto para el usuario'
            ], 404);
        }

        $marcas = DB::table('inv_marcas_nuevos')
            ->where('id_familia', $id_familia)
            ->where('idProyecto', $id_proyecto)
            ->get();

        $indices = DB::table('indices_listas')
            ->where('id_familia', $id_familia)
            ->where('idProyecto', $id_proyecto)
            ->where('idAtributo', 2)
            ->get();

        $resultado = $marcas->concat($indices)
            ->unique('descripcion')
            ->values();

        return response()->json($resultado, 200);
    }

    public function indiceColores($idCiclo = null)
    {
        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        if (!$id_proyecto && $idCiclo) {
            $id_proyecto = DB::table('inv_ciclos')->where('idCiclo', $idCiclo)->value('id_proyecto');
        }

        if (empty($id_proyecto)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontró proyecto para el usuario'
            ], 404);
        }

        $collection = IndiceListaColores::where('idProyecto', $id_proyecto)
            ->get();

        return response()->json($collection, 200);
    }

    public function estadosOperacional($idCiclo = null)
    {
        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        if (!$id_proyecto && $idCiclo) {
            $id_proyecto = DB::table('inv_ciclos')->where('idCiclo', $idCiclo)->value('id_proyecto');
        }

        if (empty($id_proyecto)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontró proyecto para el usuario'
            ], 404);
        }

        $collection = IndiceListaOperacional::where('idProyecto', $id_proyecto)
            ->get();

        return response()->json($collection, 200);
    }

    public function tipoTrabajo($idCiclo = null)
    {
        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        if (!$id_proyecto && $idCiclo) {
            $id_proyecto = DB::table('inv_ciclos')->where('idCiclo', $idCiclo)->value('id_proyecto');
        }

        if (empty($id_proyecto)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontró proyecto para el usuario'
            ], 404);
        }

        $collection = IndiceListaTipoTrabajo::where('idProyecto', $id_proyecto)
            ->get();

        return response()->json($collection, 200);
    }

    public function cargaTrabajo($idCiclo = null)
    {
        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        if (!$id_proyecto && $idCiclo) {
            $id_proyecto = DB::table('inv_ciclos')->where('idCiclo', $idCiclo)->value('id_proyecto');
        }

        if (empty($id_proyecto)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontró proyecto para el usuario'
            ], 404);
        }

        $collection = IndiceListaCargaTrabajo::where('idProyecto', $id_proyecto)
            ->get();

        return response()->json($collection, 200);
    }

    public function estadoConservacion($idCiclo = null)
    {
        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        if (!$id_proyecto && $idCiclo) {
            $id_proyecto = DB::table('inv_ciclos')->where('idCiclo', $idCiclo)->value('id_proyecto');
        }

        if (empty($id_proyecto)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontró proyecto para el usuario'
            ], 404);
        }

        $collection = IndiceListaConservacion::where('idProyecto', $id_proyecto)
            ->get();

        return response()->json($collection, 200);
    }

    public function condicionAmbiental($idCiclo = null)
    {
        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        if (!$id_proyecto && $idCiclo) {
            $id_proyecto = DB::table('inv_ciclos')->where('idCiclo', $idCiclo)->value('id_proyecto');
        }

        if (empty($id_proyecto)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontró proyecto para el usuario'
            ], 404);
        }

        $collection = IndiceListaCondicionAmbiental::where('idProyecto', $id_proyecto)
            ->get();

        return response()->json($collection, 200);
    }

    public function material($idCiclo = null)
    {
        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        if (!$id_proyecto && $idCiclo) {
            $id_proyecto = DB::table('inv_ciclos')->where('idCiclo', $idCiclo)->value('id_proyecto');
        }

        if (empty($id_proyecto)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontró proyecto para el usuario'
            ], 404);
        }

        $collection = IndiceListaMaterial::where('idProyecto', $id_proyecto)
            ->get();

        return response()->json($collection, 200);
    }

    /**
     * Se busca el forma por familia.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\IndiceListaForma  
     * @return \Illuminate\Http\Response
     */
    public function forma($idCiclo = null)
    {
        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        if (!$id_proyecto && $idCiclo) {
            $id_proyecto = DB::table('inv_ciclos')->where('idCiclo', $idCiclo)->value('id_proyecto');
        }

        if (empty($id_proyecto)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontró proyecto para el usuario'
            ], 404);
        }

        $collection = IndiceListaForma::where('idProyecto', $id_proyecto)
            ->get();

        return response()->json($collection, 200);
    }

    public function getNombre()
    {
        $user = Auth::user();
        $usuario = $user->name;
        $responsable = DB::table('sec_users')->where('login', $usuario)->value('name');
        return $responsable;
    }

    /**
     * create bienes nuevos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Inventario_bienes  
     * @param \Illuminate\Http\Indicelista se busca maximo idLista
     * @return \Illuminate\Http\Response
     */

    public function createBienes(Request $request)
    {
        $crearIndice = DB::connection()->getDatabaseName() === 'taxochil_ac-ecuessence';

        $request->validate([
            'descripcion'       => 'required|string',
            // Ecuessence puede comenzar con el catálogo vacío.
            'idAtributo'        => $crearIndice ? 'required|integer|min:1' : 'required|exists:indices_listas,idAtributo',
            'id_familia'        => 'required|exists:dp_familias,id_familia',
            'id_grupo'          => 'required|exists:dp_grupos,id_grupo',
            'ciclo_inventario'  => 'required|exists:inv_ciclos,idCiclo'
        ]);

        if ($crearIndice) {
            // Invocación interna: el cliente móvil mantiene una sola petición.
            $respuestaIndice = $this->createIndiceLista($request);
            if ($respuestaIndice->getStatusCode() >= 400) {
                return $respuestaIndice;
            }
        }

        if (!$request->observacion) {
            $request->merge(['observacion' => 'Sin observación',]);
        }

        $sql  = "SELECT * FROM inv_bienes_nuevos WHERE idIndice = $request->id_familia AND idAtributo = $request->idAtributo";

        $indice = DB::selectOne($sql);

        $maxListaIndicelista = Indicelista::where('idAtributo', $request->idAtributo)
            ->where('idIndice', $request->id_familia)
            ->max('idLista');

        $maxListaMarcasNuevos = Inventario_bienes::where('idAtributo', $request->idAtributo)
            ->where('id_familia', $request->id_familia)
            ->max('idLista');

        if ($maxListaIndicelista === null && $maxListaMarcasNuevos === null) {
            $newIdLista = 1;
        } else {
            $newIdLista = max($maxListaIndicelista, $maxListaMarcasNuevos) + 1;
        }

        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        $usuario = Auth::user()->name;

        $bienes = new Inventario_bienes();
        $bienes->idLista     = $newIdLista;
        $bienes->idProyecto  = $id_proyecto;
        $bienes->idIndice    = $request->id_familia;
        $bienes->descripcion = $request->descripcion;
        $bienes->observacion = $request->observacion;
        $bienes->idAtributo  = $request->idAtributo;
        $bienes->id_familia  = $request->id_familia;
        $bienes->id_grupo    = $request->id_grupo;
        $bienes->ciclo_inventario = $request->ciclo_inventario;
        $bienes->creadoPor    = $usuario;
        $bienes->fechaModificacion = date('Y-m-d H:i:s');
        $bienes->fechaCreacion = date('Y-m-d H:i:s');
        $bienes->modo          = 'ONLINE';
        $bienes->save();

        return response()->json($bienes, 201);
    }

    /**
     * Creates the index-list entry required before creating a new good.
     * The operation is idempotent for project + attribute + family + description.
     */
    public function createIndiceLista(Request $request)
    {
        // La creación del catálogo por esta API está habilitada solo para Ecuessence.
        // Se comprueba la base efectiva seleccionada por DatabaseSwitcher.
        if (DB::connection()->getDatabaseName() !== 'taxochil_ac-ecuessence') {
            return response()->json([
                'status' => 'error',
                'message' => 'La creación de índices está habilitada únicamente para Ecuessence',
            ], 403);
        }

        $request->validate([
            'descripcion'      => 'required|string|max:100',
            'observacion'      => 'nullable|string|max:100',
            'id_familia'       => 'required|integer|exists:dp_familias,id_familia',
            'id_grupo'         => 'required|integer|exists:dp_grupos,id_grupo',
            'idAtributo'       => 'sometimes|integer|min:1',
            'ciclo_inventario' => 'nullable|integer|exists:inv_ciclos,idCiclo',
        ]);

        $idAtributo = (int) $request->input('idAtributo', 1);
        $idFamilia = (int) $request->input('id_familia');
        $idGrupo = (int) $request->input('id_grupo');
        $descripcion = trim($request->input('descripcion'));
        $idProyecto = ProyectoUsuarioService::getIdProyecto();

        if (!$idProyecto) {
            return response()->json([
                'status' => 'error',
                'message' => 'El usuario no tiene un proyecto asociado',
            ], 422);
        }

        // id_grupo no existe en indices_listas; se usa para validar la relación.
        $familiaPerteneceAlGrupo = DB::table('dp_familias')
            ->where('id_familia', $idFamilia)
            ->where('id_grupo', $idGrupo)
            ->where('id_proyecto', $idProyecto)
            ->where('soft_delete', 'N')
            ->exists();

        $grupoDisponible = DB::table('dp_grupos')
            ->where('id_grupo', $idGrupo)
            ->where('id_proyecto', $idProyecto)
            ->where('soft_delete', 'N')
            ->exists();

        if (!$familiaPerteneceAlGrupo || !$grupoDisponible) {
            return response()->json([
                'status' => 'error',
                'message' => 'El grupo y la familia deben estar activos, pertenecer al proyecto del usuario y estar relacionados',
            ], 422);
        }

        // Para los bienes, el índice corresponde a la familia.
        $idIndice = (string) $idFamilia;

        // Dipreca demuestra que una familia puede tener muchos bienes.
        // Por eso la descripción también forma parte de la identidad lógica.
        $existente = IndiceLista::query()
            ->where('idAtributo', $idAtributo)
            ->where('idIndice', $idIndice)
            ->where('idProyecto', $idProyecto)
            ->where('id_familia', $idFamilia)
            ->whereRaw('LOWER(TRIM(descripcion)) = ?', [mb_strtolower($descripcion, 'UTF-8')])
            ->first();

        if ($existente) {
            return response()->json([
                'status' => 'OK',
                'created' => false,
                'message' => 'El índice ya existe; se reutiliza el registro existente',
                'data' => $existente,
            ], 200);
        }

        $usuario = Auth::user()->name;
        $ahora = date('Y-m-d H:i:s');

        $indice = new IndiceLista();
        // La tabla asigna idLista mediante AUTO_INCREMENT. No calcular MAX + 1.
        $indice->setKeyName('idLista');
        $indice->idAtributo = $idAtributo;
        $indice->idIndice = $idIndice;
        $indice->idProyecto = $idProyecto;
        $indice->id_familia = $idFamilia;
        $indice->descripcion = $descripcion;
        $indice->observacion = $request->input('observacion', 'Sin observación');
        $indice->listaRapida = '0';
        $indice->creadoPor = $usuario;
        $indice->fechaCreacion = $ahora;
        $indice->ciclo_inventario = (int) $request->input('ciclo_inventario', 0);
        $indice->en_listas = 0;
        $indice->modo = 'ONLINE';
        $indice->creado_por = $usuario;
        $indice->creado_el = $ahora;
        $indice->fl_catalogo_activo = 1;
        $indice->fl_creado_auto = 1;
        $indice->save();

        return response()->json([
            'status' => 'OK',
            'created' => true,
            'message' => 'Índice creado correctamente',
            'data' => $indice,
        ], 201);
    }

    /**
     * create marcas nuevas.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Inventario_marcas  
     * @param \Illuminate\Http\Indicelista se busca maximo idLista
     * @return \Illuminate\Http\Response
     */
    public function createMarcas(Request $request)
    {
        $request->validate([
            'descripcion'       => 'required|string',
            'idAtributo'        => 'required',
            'id_familia'        => 'required|exists:dp_familias,id_familia',
            'ciclo_inventario'  => 'required|exists:inv_ciclos,idCiclo'
        ]);

        if (!$request->observacion) {
            $request->merge(['observacion' => 'Sin Observación']);
        }

        $sql  = "SELECT * FROM inv_marcas_nuevos WHERE idIndice = $request->id_familia AND idAtributo = $request->idAtributo";

        $indice = DB::selectOne($sql);

        $maxListaIndicelista = Indicelista::where('idAtributo', $request->idAtributo)
            ->where('idIndice', $request->id_familia)
            ->max('idLista');

        $maxListaMarcasNuevos = Inventario_marcas::where('idAtributo', $request->idAtributo)
            ->where('id_familia', $request->id_familia)
            ->max('idLista');

        if ($maxListaIndicelista === null && $maxListaMarcasNuevos === null) {
            $newIdLista = 1;
        } else {
            $newIdLista = max($maxListaIndicelista, $maxListaMarcasNuevos) + 1;
        }

        $usuario = Auth::user()->name;

        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        $marcas = new Inventario_marcas();
        $marcas->idLista     = $newIdLista;
        $marcas->idProyecto  = $id_proyecto;
        $marcas->idIndice    = $request->id_familia;
        $marcas->descripcion = $request->descripcion;
        $marcas->observacion = $request->observacion;
        $marcas->idAtributo  = $request->idAtributo;
        $marcas->id_familia  = $request->id_familia;
        $marcas->ciclo_inventario = $request->ciclo_inventario;
        $marcas->creadoPor     = $usuario;
        $marcas->fechaCreacion = date('Y-m-d H:i:s');
        $marcas->fechaModificacion = date('Y-m-d H:i:s');
        $marcas->modo          = 'ONLINE';
        $marcas->save();

        return response()->json($marcas, 201);
    }


    public function getAllInvPropertyData()
    {

        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        $estados = IndiceListaEstado::where('idProyecto', $id_proyecto)->get();
        $colores = IndiceListaColores::where('idProyecto', $id_proyecto)->get();
        $estados_operacionales = IndiceListaOperacional::where('idProyecto', $id_proyecto)->get();
        $estados_conservacion = IndiceListaConservacion::where('idProyecto', $id_proyecto)->get();
        $tipos_trabajo = IndiceListaTipoTrabajo::where('idProyecto', $id_proyecto)->get();
        $condiciones_ambientales = IndiceListaCondicionAmbiental::where('idProyecto', $id_proyecto)->get();
        $cargas_trabajo = IndiceListaCargaTrabajo::where('idProyecto', $id_proyecto)->get();
        $formas = IndiceListaForma::where('idProyecto', $id_proyecto)->get();
        $materiales = IndiceListaMaterial::where('idProyecto', $id_proyecto)->get();



        return response()->json([
            'estados' => $estados,
            'colores' => $colores,
            'estados_operacionales' => $estados_operacionales,
            'estados_conservacion' => $estados_conservacion,
            'tipos_trabajo' => $tipos_trabajo,
            'condiciones_ambientales' => $condiciones_ambientales,
            'cargas_trabajo' => $cargas_trabajo,
            'formas' => $formas,
            'materiales' => $materiales
        ], 200);
    }

    public function syncAllInvPropertyData(Request $request, int $cycle_id)
    {
        $lastDateMarca = $request->lastDateMarca && !empty($request->lastDateMarca) ? $request->lastDateMarca : '2000-01-01 00:00:00';
        $lastDateBienesTipos = $request->lastDateBienesTipos && !empty($request->lastDateBienesTipos) ? $request->lastDateBienesTipos : '2000-01-01 00:00:00';
        return response()->json([
            'status' => 'OK',
            'data' => [
                'marcas' => Inventario_marcas::where('ciclo_inventario', $cycle_id)->where('fechaCreacion', '>', $lastDateMarca)->get(),
                'bienesTipos' => Inventario_bienes::where('ciclo_inventario', $cycle_id)->where('fechaCreacion', '>', $lastDateBienesTipos)->get(),
            ]
        ]);
    }
}
