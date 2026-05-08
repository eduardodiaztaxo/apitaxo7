<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\EmplazamientoResource;
use App\Http\Resources\V2\EmplazamientoNivel2Resource;
use App\Http\Resources\V2\EmplazamientoNivel3Resource;
use App\Http\Resources\V2\EmplazamientoGenericoResource;
use App\Http\Resources\V1\EmplazamientoNivel1Resource;
use App\Http\Resources\V1\EmplazamientoAllResource;
use App\Http\Resources\V2\CrudActivoResource;
use App\Http\Resources\V2\EmplazamientoNnLiteResource;
use App\Http\Resources\V2\Inventario\EmplazamientoNnResource;
use App\Http\Resources\V2\InventariosResource;
use App\Services\ActivoFinderService;
use App\Services\ProyectoUsuarioService;
use App\Models\CrudActivo;
use App\Models\InvCiclo;
use App\Models\Emplazamiento;
use App\Models\EmplazamientoN2;
use App\Models\EmplazamientoN1;
use App\Models\EmplazamientoN3;
use App\Models\Inventario;
use App\Models\Inventario\EmplazamientoNn;
use App\Models\ZonaPunto;
use App\Services\PlaceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EmplazamientoController extends Controller
{
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
     * Create new resource.
     *
     * @param  \Illuminate\Http\Request     $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request) {}





    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'descripcion'     => 'required|string',
            'zona_id'         => 'required|exists:ubicaciones_n1,codigoUbicacion',
            'agenda_id'       => 'required|exists:ubicaciones_geograficas,idUbicacionGeo',
            'estado'          => 'sometimes|required|in:0,1',
            'ciclo_auditoria' => 'required'
        ]);

        $idMax = DB::table('ubicaciones_n2')
            ->where('idAgenda', $request->agenda_id)
            ->where('codigoUbicacion', 'like', $request->zona_id . '%')
            ->max('codigoUbicacion');

        $zonaId = $request->zona_id;

        if ($idMax) {
            $num = substr($idMax, strlen($zonaId));
            $numIncrementado = str_pad((int)$num + 1, strlen($num), '0', STR_PAD_LEFT);
            $code = $zonaId . $numIncrementado;
        } else {
            $code = $zonaId . '01';
        }

        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        $data = [
            'idProyecto'            => $id_proyecto,
            'idAgenda'              => $request->agenda_id,
            'descripcionUbicacion'  => $request->descripcion,
            'codigoUbicacion'       => $code,
            'fechaCreacion'         => date('Y-m-d H:i:s'),
            'estado'                => $request->estado !== null ? $request->estado : 1,
            'usuario'               => $request->user()->name,
            'ciclo_auditoria'       => $request->ciclo_auditoria,
            'newApp'                => 1,
            'modo'                  => 'ONLINE'
        ];

        $empla = EmplazamientoN2::create($data);

        if (!$empla) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo crear el emplazamiento'
            ], 422);
        }

        return response()->json([
            'status'  => 'OK',
            'message' => 'Creado exitosamente',
            'data'    => EmplazamientoNivel2Resource::make($empla)
        ]);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeAnyLevel(Request $request)
    {


        $request->validate([
            'description'     => 'required|string',
            'parentCode'      => 'required',
            'agenda_id'       => 'required|exists:ubicaciones_geograficas,idUbicacionGeo',
            'level'           => 'required|integer|min:1|max:6',
            'cycle'           => 'required|integer'
        ]);

        $cycleObj = InvCiclo::find($request->cycle);

        if (!$cycleObj) {
            return response()->json(['status' => 'error', 'code' => 404, 'messaje' => 'cycle not found'], 404);
        }

        $table = 'ubicaciones_n' . $request->level;

        $code =  EmplazamientoNn::fromTable($table)->nextCode($request->agenda_id, $request->parentCode);

        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        $data = [
            'idProyecto'            => $id_proyecto,
            'idAgenda'              => $request->agenda_id,
            'descripcionUbicacion'  => $request->description,
            'codigoUbicacion'       => $code,
            'fechaCreacion'         => date('Y-m-d H:i:s'),
            'estado'                => $request->estado !== null ? $request->estado : 1,
            'usuario'               => $request->user()->name,
            'ciclo_auditoria'       => $request->cycle,
            'newApp'                => 1,
            'modo'                  => 'ONLINE'
        ];

        $empla = EmplazamientoNn::fromTable($table)->create($data);

        if (!$empla) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo crear el emplazamiento'
            ], 422);
        }

        return response()->json([
            'status'  => 'OK',
            'message' => 'Creado exitosamente',
            'colle' => $empla,
            'data'    => new EmplazamientoNnResource($empla, $cycleObj, $request->level)
        ]);
    }


    /**
     * Create sub emplazamientos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    //este falla al crear uno nuevo e ingresar inmediatamente
    //fijar añadiendo propiedad zone_address
    public function createSubEmplazamientosNivel3(Request $request)
    {

        //return (ZonaPunto::where('idAgenda', '=', $request->agenda_id)->where('codigoUbicacion', '=', $request->codigoUbicacion)->toSql());

        $request->validate([
            'descripcion'      => 'required|string',
            'agenda_id'        => 'required|exists:ubicaciones_geograficas,idUbicacionGeo',
            'ciclo'            => 'required',
            'codigoUbicacion'  => 'required|exists:ubicaciones_n2,codigoUbicacion'
        ]);

        $baseCodigo = $request->codigoUbicacion;

        $subCodigos = DB::table('ubicaciones_n3')
            ->where('codigoUbicacion', 'like', $baseCodigo . '%')
            ->pluck('codigoUbicacion');

        $maxSecuencia = $subCodigos
            ->map(function ($codigo) use ($baseCodigo) {
                return intval(substr($codigo, strlen($baseCodigo), 2));
            })
            ->max();

        $nuevoSufijo = str_pad(($maxSecuencia + 1), 2, '0', STR_PAD_LEFT);
        $nuevoCodigoUbicacionN3 = $baseCodigo . $nuevoSufijo;

        $id_proyecto = ProyectoUsuarioService::getIdProyecto();

        $data = [
            'idProyecto'           => $id_proyecto,
            'idAgenda'             => $request->agenda_id,
            'descripcionUbicacion' => $request->descripcion,
            'codigoUbicacion'      => $nuevoCodigoUbicacionN3,
            'usuario'              => $request->user()->name,
            'estado'               => 1,
            'fechaCreacion'        => date('Y-m-d H:i:s'),
            'newApp'               => 1,
            'modo'                 => 'ONLINE'
        ];

        $num_activos_cats_by_cycleN3 = DB::table('crud_activos')
            ->where('ubicacionOrganicaN4', $nuevoCodigoUbicacionN3)
            ->count();

        $num_activos_invN3 = DB::table('inv_inventario')
            ->where('codigoUbicacionN3', $nuevoCodigoUbicacionN3)
            ->count();

        $empla = EmplazamientoN3::create($data);

        if (!$empla) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No se pudo crear el emplazamiento'
            ], 422);
        }

        $zone_address = ZonaPunto::where('idAgenda', '=', $empla->idAgenda)
            ->where('codigoUbicacion', '=', substr($empla->codigoUbicacion, 0, 2))->first();

        return response()->json([
            'status'  => 'OK',
            'message' => 'Creado exitosamente',
            'data'    => EmplazamientoNivel3Resource::make($empla)
        ]);
    }



    /**
     * check if exists emplazamiento.
     *
     * @param  int  $punto
     * @param  int  $emplazamiento_code 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function existsEmplazamiento(int $punto, string $emplazamiento_code, Request $request)
    {
        $length_code = strlen((string) $emplazamiento_code);

        // Validar que la longitud sea par (2, 4, 6, 8, 10, etc.)
        if ($length_code % 2 !== 0) {
            return response()->json(['status' => 'error', 'message' => 'Código de emplazamiento no válido'], 404);
        }

        // Calcular el nivel dinámicamente (2 dígitos = N1, 4 = N2, 6 = N3, 8 = N4, 10 = N5, etc.)
        $nivel = $length_code / 2;
        $nivelString = 'N' . $nivel;

        // Determinar el nombre de la tabla dinámicamente
        $tableName = 'ubicaciones_n' . $nivel;

        // Buscar en la tabla correspondiente
        $emplazamientoObj = DB::table($tableName)
            ->where('idAgenda', $punto)
            ->where('codigoUbicacion', $emplazamiento_code)
            ->first();

        if (!$emplazamientoObj) {
            return response()->json(['status' => 'OK', 'data' => [
                'exists' => false,
                'emplazamiento' => null
            ]]);
        }

        $emplazamientoObj = (object) $emplazamientoObj;

        $emplazamientoObj->requirePunto = 1;

        return response()->json(['status' => 'OK', 'data' => [
            'exists' => true,
            'emplazamiento' => EmplazamientoGenericoResource::makeWithNivel($emplazamientoObj, $nivelString)
        ]]);
    }


    /**
     * Display assets of the specified resource.
     *
     *  
     * @param   int $emplazamiento
     * @param   \Illuminate\Http\Request
     * @return  \Illuminate\Http\Response
     */
    public function showAssets(int $emplazamiento, Request $request)
    {

        $emplaN1Obj = EmplazamientoN1::find($emplazamiento);

        if (!$emplaN1Obj) {
            return response()->json(['status' => 'error', 'code' => 404], 404);
        }


        $queryBuilder = $queryBuilder = CrudActivo::queryBuilderCrudActivo_FindInGroupFamily_Pagination($emplaN1Obj, $request);

        $assets = $queryBuilder->get();

        //
        return response()->json([
            'status' => 'OK',
            'data' => CrudActivoResource::collection($assets)
        ]);
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $emplazamiento_id
     * @return \Illuminate\Http\Response
     */


    public function show(int $ciclo, $param2, $param3 = null)
    {
        // --- 1. DETECCIÓN DE PARÁMETROS ---
        if ($param3 !== null) {
            // FLUJO NUEVO: ciclos/{ciclo}/emplazamientos/{nivel}/{emplazamiento}
            $nivel = (int) $param2;
            $emplazamientoId = $param3;
        } else {
            // FLUJO ANTIGUO: ciclos/{ciclo}/emplazamientos-n[1,2,3]/{emplazamiento}
            $emplazamientoId = $param2;

            $uri = request()->route()->uri();
            if (str_contains($uri, 'emplazamientos-n1')) $nivel = 1;
            elseif (str_contains($uri, 'emplazamientos-n2')) $nivel = 2;
            else $nivel = 3;
        }


        [$emplaObj, $resourceClass] = match ($nivel) {
            1 => [
                EmplazamientoN1::where('idUbicacionN1', $emplazamientoId)->first(),
                EmplazamientoNivel1Resource::class
            ],
            2 => [
                Emplazamiento::where('idUbicacionN2', $emplazamientoId)->first(),
                EmplazamientoResource::class
            ],
            3 => [
                EmplazamientoN3::where('idUbicacionN3', $emplazamientoId)->first(),
                EmplazamientoNivel3Resource::class
            ],
            default => [null, null],
        };

        if (!$emplaObj) {
            return response()->json(['status' => 'NOK', 'code' => 404], 404);
        }

        // --- 3. ASIGNACIÓN DE DATOS EXTRA ---
        $emplaObj->requirePunto = 1;
        $emplaObj->requireActivos = 1;
        $emplaObj->cycle_id = $ciclo;

        return response()->json($resourceClass::make($emplaObj));
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $emplazamiento_id
     * @return \Illuminate\Http\Response
     */


    public function showOne(int $emplazamiento, $ciclo, $codigoUbicacion)
    {


        $nivel = strlen($codigoUbicacion) / 2;



        [$emplaObj, $resourceClass] = match ($nivel) {
            1 => [
                EmplazamientoN1::where('idUbicacionN1', $emplazamiento)->first(),
                EmplazamientoNivel1Resource::class
            ],
            2 => [
                Emplazamiento::where('idUbicacionN2', $emplazamiento)->first(),
                EmplazamientoResource::class
            ],
            3 => [
                EmplazamientoN3::where('idUbicacionN3', $emplazamiento)->first(),
                EmplazamientoNivel3Resource::class
            ],
            default => [null, null],
        };

        if (!$emplaObj) {
            return response()->json(['status' => 'NOK', 'code' => 404], 404);
        }

        // --- 3. ASIGNACIÓN DE DATOS EXTRA ---
        $emplaObj->requirePunto = 1;
        $emplaObj->requireActivos = 1;
        $emplaObj->cycle_id = $ciclo;

        return response()->json($resourceClass::make($emplaObj));
    }

    /**
     * Devuelve los emplazamientos de un punto para un ciclo.
     *
     * La ruta original suministraba sólo {idAgenda}/{ciclo} y siempre
     * retornaba un N1 con todos los hijos (EmplazamientoAllResource).
     *
     * A partir de ahora aceptamos un tercer segmento opcional `nivel`.
     * Si se proporciona se generará un recurso genérico para el nivel
     * indicado ("N2", "N3", etc). Cuando no hay nivel se mantiene el
     * comportamiento antiguo.
     *
     * Ejemplos de URL válidas:
     *   /api/v1/todos-emplazamientos/631/0          -> nivel N1 (antiguo)
     *   /api/v1/todos-emplazamientos/631/0/2        -> también N1 (idéntico)
     *   /api/v1/todos-emplazamientos/631/0/3        -> primer N2 del punto
     *   /api/v1/todos-emplazamientos/631/0/5        -> primer N4 si existe
     *
     * @param int $idAgenda
     * @param int $ciclo
     * @param int|null $nivel
     * @return \Illuminate\Http\Response
     */
    public function showTodos(int $idAgenda, int $ciclo, int $nivel = null)
    {
        // si no se especifica nivel, tomamos N1 y usamos el flujo antiguo
        if ($nivel === null || $nivel === 1) {
            $emplaObj = EmplazamientoN1::with(['emplazamientosN2', 'emplazamientosN3'])
                ->where('idAgenda', $idAgenda)
                ->first();

            if (!$emplaObj) {
                return response()->json(['status' => 'NOK', 'code' => 404], 404);
            }

            $emplaObj->requirePunto = 1;
            $emplaObj->requireActivos = 1;
            $emplaObj->cycle_id = $ciclo;

            return response()->json(EmplazamientoAllResource::make($emplaObj));
        }

        // recurso genérico para cualquier otro nivel
        $nivelString = 'N' . $nivel;
        $tableName = 'ubicaciones_n' . $nivel;

        $emplaObj = DB::table($tableName)
            ->where('idAgenda', $idAgenda)
            ->first();

        if (!$emplaObj) {
            return response()->json(['status' => 'NOK', 'code' => 404], 404);
        }

        $emplaObj = (object) $emplaObj;
        $emplaObj->cycle_id = $ciclo;

        return response()->json(EmplazamientoGenericoResource::makeWithNivel($emplaObj, $nivelString));
    }

    public function showTodosEmplazamientosN1N2N3(int $lastN1Id, int $lastN2Id, int $lastN3Id)
    {
        $emplazamientosN1 = EmplazamientoN1::where('idUbicacionN1', '>', $lastN1Id)->get();
        $emplazamientosN2 = EmplazamientoN2::where('idUbicacionN2', '>', $lastN2Id)->get();
        $emplazamientosN3 = EmplazamientoN3::where('idUbicacionN3', '>', $lastN3Id)->get();

        return response()->json([
            'status' => 'OK',
            'data' => [
                'n1' => EmplazamientoNivel1Resource::collection($emplazamientosN1),
                'n2' => EmplazamientoNivel2Resource::collection($emplazamientosN2),
                'n3' => EmplazamientoNivel3Resource::collection($emplazamientosN3)
            ]
        ]);
    }

    public function showTodosEmplazamientosSixLevels(int $cycle_id, int $lastN1Id, int $lastN2Id, int $lastN3Id, int $lastN4Id, int $lastN5Id, int $lastN6Id)
    {


        $cycleObj = InvCiclo::find($cycle_id);

        $data = [];
        for ($i = 1; $i <= 6; $i++) {
            $varIDName = 'lastN' . $i . 'Id';
            $currentTable = 'ubicaciones_n' . $i;
            $emplazamientos = EmplazamientoNn::fromTable($currentTable)->where('idUbicacionN' . $i, '>', ${$varIDName})->get();
            $resources = $emplazamientos->map(function ($emplazamiento) use ($i, $cycleObj) {
                return new EmplazamientoNnResource($emplazamiento, $cycleObj, $i);
            });

            $data['n' . $i] = $resources;
        }

        return response()->json([
            'status' => 'OK',
            'data' => $data
        ]);
    }

    /**
     * Display assets of a emplazamiento by dynamic level.
     *
     * @param int $idAgenda
     * @param int $ciclo
     * @param int $nivel
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function showTodosAssets(int $idAgenda, int $ciclo, int $nivel, string $codigoUbicacion, Request $request)
    {
        // 1. Nombre de la columna dinámica según el nivel
        $columnaFiltroNivel = "ubicacionOrganicaN{$nivel}_codigo";
        $perPage = 50;

        // Compatibilidad con el parámetro histórico `from`.
        // La nueva paginación estándar usa `page`, pero si el consumidor
        // aún envía `from`, lo convertimos a la página correspondiente.
        $page = (int) $request->query('page', 1);
        if ($page < 1) {
            $page = 1;
        }

        if ($request->filled('from') && ! $request->filled('page')) {
            $from = max(1, (int) $request->query('from'));
            $page = (int) ceil($from / $perPage);
        }

        // 2. Construcción de la consulta directa a la vista con payload mínimo
        $ultimaFotoSubquery = DB::table('crud_activos_pictures')
            ->select('id_activo', DB::raw('MAX(id_foto) as id_foto'))
            ->groupBy('id_activo');

        $queryBuilder = DB::table('crud_activos_view as cav')
            ->leftJoinSub($ultimaFotoSubquery, 'ultima_foto', function ($join) {
                $join->on('ultima_foto.id_activo', '=', 'cav.idActivo');
            })
            ->leftJoin('crud_activos_pictures as cap', 'cap.id_foto', '=', 'ultima_foto.id_foto')
            ->select([
                'cav.idActivo as id',
                'cav.nombreActivo',
                'cav.etiqueta',
                DB::raw("COALESCE(CONCAT(cap.url_picture, '/', cap.picture), '" . asset('img/notavailable.jpg') . "') as fotoUrl"),
            ])
            ->where('cav.direccion_id', $idAgenda)
            ->where('cav.tipoCambio', '!=', 91)
            ->where("cav.$columnaFiltroNivel", $codigoUbicacion);

        // 3. Filtro de Ciclo (solo si la vista expone la columna)
        if ($ciclo != 0 && Schema::hasColumn('crud_activos_view', 'id_ciclo')) {
            $queryBuilder->where('cav.id_ciclo', $ciclo);
        }

        // 4. Filtro de Búsqueda
        if (!!keyword_is_searcheable($request->keyword)) {
            $word = trim($request->keyword);
            $queryBuilder->where(function ($query) use ($word) {
                $query->where('cav.nombreActivo', 'LIKE', "%$word%")
                    ->orWhere('cav.etiqueta', 'LIKE', "%$word%");
            });
        }

        // 5. Paginación estándar de 50 registros por página.
        $assets = $queryBuilder
            ->orderBy('etiqueta')
            ->paginate($perPage, ['*'], 'page', $page)
            ->appends($request->query());

        return response()->json([
            'status' => 'OK',
            'data' => $assets->items(),
            'meta' => [
                'current_page' => $assets->currentPage(),
                'from' => $assets->firstItem(),
                'last_page' => $assets->lastPage(),
                'per_page' => $assets->perPage(),
                'to' => $assets->lastItem(),
                'total' => $assets->total(),
            ],
            'links' => [
                'first' => $assets->url(1),
                'last' => $assets->url($assets->lastPage()),
                'prev' => $assets->previousPageUrl(),
                'next' => $assets->nextPageUrl(),
            ], // Devolución paginada sin pasar por el Resource
        ]);
    }

    public function getAssetPictures(string $etiqueta)
    {
        $pictures = DB::table('crud_activos_pictures')
            ->where('etiqueta', $etiqueta)
            ->get();

        return response()->json([
            'status' => 'OK',
            'etiqueta' => $etiqueta,
            'data' => $pictures
        ]);
    }

    public function groupEmplazamientos(int $idAgenda, int $ciclo)
    {
        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json([
                'status' => 'NOK',
                'message' => 'Ciclo no encontrado',
                'code' => 404
            ], 404);
        }

        $activos = $cicloObj->activos_with_cats_by_cycle_emplazamiento($ciclo, $idAgenda);

        return response()->json([
            'status' => 'OK',
            'message' => 'Emplazamientos obtenidos correctamente',
            'data' => [
                'emplazamientos' => $activos,
            ],
        ], 200);
    }

    public function groupEmplazamientosPorOt(int $ciclo)
    {
        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json([
                'status' => 'NOK',
                'message' => 'Ciclo no encontrado',
                'code' => 404
            ], 404);
        }

        $activos = $cicloObj->activos_with_cats_by_cycle_emplazamiento_por_ot($ciclo);

        return response()->json([
            'status' => 'OK',
            'message' => 'Emplazamientos obtenidos correctamente',
            'data' => [
                'emplazamientos' => $activos
            ],
        ], 200);
    }

    public function groupMapDireccionDiferencias(int $idAgenda, int $ciclo)
    {
        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json([
                'status' => 'NOK',
                'message' => 'Ciclo no encontrado',
                'code' => 404
            ], 404);
        }

        $diferencias = $cicloObj->diferencias_por_direcciones($ciclo, $idAgenda);
        $total = array_sum(array_map(function ($item) {
            return $item->q_teorico ?? 0;
        }, $diferencias));


        return response()->json([
            'status' => 'OK',
            'message' => 'Diferencias obtenidas correctamente',
            'data' => [
                $diferencias,
                $total
            ],
        ], 200);
    }

    public function groupMapDiferenciasOT(int $ciclo)
    {
        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json([
                'status' => 'NOK',
                'message' => 'Ciclo no encontrado',
                'code' => 404
            ], 404);
        }

        $puntos = $cicloObj->puntos()->pluck('idUbicacionGeo')->toArray();

        if (empty($puntos)) {
            return response()->json(['status' => 'NOK', 'code' => 404], 404);
        }

        $diferencias = $cicloObj->diferencias_por_puntos_OT($puntos);
        $total = array_sum(array_map(function ($item) {
            return $item->q_teorico ?? 0;
        }, $diferencias));

        return response()->json([
            'status' => 'OK',
            'message' => 'Diferencias por OT obtenidas correctamente',
            'data' => [
                $diferencias,
                $total
            ],
        ], 200);
    }

    public function selectEmplazamientosNn(Request $request, int $ciclo, int $nivel, int $agenda_id)
    {

        $currentTable = 'ubicaciones_n' . $nivel;
        $emplazamientosObjs = EmplazamientoNn::fromTable($currentTable)->where('idAgenda', '=', $agenda_id)->get();

        if ($emplazamientosObjs->isEmpty()) {
            return response()->json([], 200);
        }

        $cicloObj = InvCiclo::find($ciclo);

        if (!$cicloObj) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ciclo no encontrado',
                'code' => 404
            ], 404);
        }

        return response()->json(['status' => 'OK', 'data' => EmplazamientoNnLiteResource::collection($emplazamientosObjs)], 200);
    }

    public function showEmplazamientosRecursiveTreeView(Request $request, int $agenda_id)
    {

        if (!keyword_is_searcheable($request->keyword)) {
            return response()->json([
                'status' => 'OK',
                'data' => []
            ]);
        }

        $complete_word = trim($request->keyword);
        $possible_name_words = keyword_search_terms_from_keyword($request->keyword);
        $levels_nn_results = [];

        $found_parents_codes = [];

        $found_children_codes = [];

        for ($i = 6; $i > 0; $i--) {



            if (!isset($found_parents_codes['n' . $i])) {
                $found_parents_codes['n' . $i] = [];
            }

            if (!isset($found_children_codes['n' . $i])) {
                $found_children_codes['n' . $i] = [];
            }

            $table = 'ubicaciones_n' . $i;
            $queryBuilder = EmplazamientoNn::fromTable($table)->where('idAgenda', $agenda_id)
                ->where('descripcionUbicacion', 'LIKE', '%' . $complete_word . '%');


            if (count($possible_name_words) > 1) {
                $queryBuilder = $queryBuilder->orWhere(function ($query) use ($possible_name_words) {
                    foreach ($possible_name_words as $palabra) {
                        $query->where('descripcionUbicacion', 'LIKE', "%$palabra%");
                    }
                });
            }

            $emplazamientos = $queryBuilder->get();

            $codes = $emplazamientos->pluck('codigoUbicacion')->toArray();


            foreach ($codes as $code) {
                for ($j = $i; $j > 0; $j--) {
                    if (!isset($found_parents_codes['n' . $j])) {
                        $found_parents_codes['n' . $j] = [];
                    }
                    $found_parents_codes['n' . $j][] = substr($code, 0, 2 * $j);
                }

                for ($k = $i + 1; $k <= 6; $k++) {
                    $nextLevel = 'n' . $k;
                    $nextTable = 'ubicaciones_n' . $k;
                    // if (in_array($code, $found_children_codes[$nextLevel])) {
                    //     break;
                    // }
                    $children_codes = EmplazamientoNn::fromTable($nextTable)
                        ->select('codigoUbicacion')->where('idAgenda', $agenda_id)
                        ->where('codigoUbicacion', 'LIKE', '' . $code . '%')
                        ->whereNotIn('codigoUbicacion', $found_children_codes[$nextLevel])
                        ->get()->pluck('codigoUbicacion')->toArray();
                    $found_children_codes[$nextLevel] = array_merge($children_codes, $found_children_codes[$nextLevel]);
                }
            }
        }

        //Consolidate, merge and delete repeat data
        $consolidate = $this->consolidateChildAndParentCodes($found_children_codes, $found_parents_codes);


        //Este array ya contiene todos los datos normalizados
        $tree_codes = $this->getTreeSubplaceFromConsolidateData($consolidate);

        return response()->json([
            'status' => 'OK',
            // 'parent_codes' => $found_parents_codes,
            // 'child_codes' => $found_children_codes,
            'consolidate' => $consolidate,
            'data' => $tree_codes,
        ], 200);
    }

    /**
     * @param array $find_children_codes['n1'=>[code1,code2,code3...coden],'n2'=>[code1,code2,code3...coden], ... 'nn'=>[code1,code2,code3...coden]]
     * @param array $find_parents_codes['n1'=>[code1,code2,code3...coden],'n2'=>[code1,code2,code3...coden], ... 'nn'=>[code1,code2,code3...coden]]
     * 
     * @return array ['n1'=>[code1,code2,code3...coden],'n2'=>[code1,code2,code3...coden], ... 'nn'=>[code1,code2,code3...coden]]
     */
    public function consolidateChildAndParentCodes(array $found_children_codes, array $found_parents_codes)
    {
        $consolidate = [];

        for ($m = 1; $m <= 6; $m++) {
            $found_codes = array_merge($found_parents_codes['n' . $m], $found_children_codes['n' . $m]);
            $consolidate['n' . $m] = array_values(array_unique($found_codes));
        }

        return $consolidate;
    }

    /**
     * @param array $consolidate ['n1'=>[code1,code2,code3], 'n2'=>[code1,code2,code3], 'n3'=>[code1,code2,code3]]
     * 
     * @return array [
     *                  [
     *                      'item' => $itemN1, 
     *                      'children' => [
     *                          ['item' => $itemN2, 'children' => $children_code],
     *                          ['item' => $itemN2, 'children' => $children_code]  
     *                      ]
     *                  ],
     *                  [
     *                      'item' => $itemN1, 
     *                      'children' => [
     *                          ['item' => $itemN2, 'children' => $children_code],
     *                          ['item' => $itemN2, 'children' => $children_code]  
     *                      ]
     *                  ]
     *              ]
     */
    public function getTreeSubplaceFromConsolidateData(array $consolidate): array
    {


        $tree_codes = [];

        $last_codes = [];

        for ($n = 6; $n > 0; $n--) {
            foreach ($consolidate['n' . $n] as $parentcode) {

                $item = $parentcode;
                $children_code = [];

                foreach ($tree_codes as $childcode) {
                    if (str_starts_with($childcode['item'], $parentcode)) {
                        $children_code[] = $childcode;
                    }
                }

                $last_codes[] = ['item' => $item, 'children' => $children_code];
            }

            //bajo el supuesto de que todos los objetos fueron organizados de forma diferente
            //Y que el arreglo contiene todo lo de consolidate en el nivel especificado 
            $tree_codes = $last_codes;

            $last_codes = [];
        }

        return $tree_codes;
    }


    // public function queryBuilderInventory($model, InvCiclo $cicloObj, Request $request)
    // {
    //     $queryBuilder = $model->inv_activos()->where('inv_inventario.id_ciclo', $cicloObj->idCiclo);

    //     if (!!keyword_is_searcheable($request->keyword)) {
    //         $complete_word = trim($request->keyword);
    //         $possible_name_words = keyword_search_terms_from_keyword($request->keyword);

    //         $queryBuilder = $queryBuilder->join('dp_familias', 'inv_inventario.id_familia', 'dp_familias.id_familia');

    //         $queryBuilder = $queryBuilder
    //             ->where(function ($query) use ($complete_word) {
    //                 $query->where('inv_inventario.descripcion_bien', 'LIKE', "%$complete_word%");
    //                 $query->orWhere('inv_inventario.etiqueta', 'LIKE', "%$complete_word%");
    //                 $query->orWhere('dp_familias.descripcion_familia', 'LIKE', "%$complete_word%");
    //             });

    //         if (count($possible_name_words) > 1) {
    //             $queryBuilder = $queryBuilder->orWhere(function ($query) use ($possible_name_words) {
    //                 foreach ($possible_name_words as $palabra) {
    //                     $query->where('inv_inventario.descripcion_bien', 'LIKE', "%$palabra%");
    //                 }
    //             });

    //             $queryBuilder = $queryBuilder->orWhere(function ($query) use ($possible_name_words) {
    //                 foreach ($possible_name_words as $palabra) {
    //                     $query->where('dp_familias.descripcion_familia', 'LIKE', "%$palabra%");
    //                 }
    //             });
    //         }
    //     }

    //     if ($request->from && $request->rows) {
    //         $offset = $request->from - 1;
    //         $limit = $request->rows;
    //         $queryBuilder->offset($offset)->limit($limit);
    //     }

    //     return $queryBuilder;
    // }

    public function moverEmplazamientos(Request $request, string $codigoUbicacion, int $ciclo_id, int $agenda_id, string $etiqueta)
    {

        $nivel = (int)(strlen($codigoUbicacion) / 2);
        $currentTable = 'ubicaciones_n' . $nivel;
        $emplaObj = EmplazamientoNn::fromTable($currentTable)->where('idAgenda', $agenda_id)
            ->where('codigoUbicacion', $codigoUbicacion)
            ->first();

        if (!$emplaObj) {
            return response()->json([
                'status' => 'NOK',
                'code' => 404,
                'data' => [
                    'idAgenda' => $agenda_id,
                    'codigoUbicacion' => $codigoUbicacion
                ]
            ], 404);
        }

        $updateData = [
            'modo' => 'ONLINE',
        ];


        $updateData['idUbicacionN2'] = $nivel === 2 ? $emplaObj->idUbicacionN2 : 0;
        $updateData['idUbicacionN3'] = $nivel === 3 ? $emplaObj->idUbicacionN3 : 0;
        $updateData['codigoUbicacion_N1'] = $nivel === 1 ? $codigoUbicacion : 0;
        $updateData['codigoUbicacion_N2'] = $nivel === 2 ? $codigoUbicacion : 0;
        $updateData['codigoUbicacionN3'] = $nivel === 3 ? $codigoUbicacion : 0;
        $updateData['codigoUbicacionN4'] = $nivel === 4 ? $codigoUbicacion : 0;
        $updateData['codigoUbicacionN5'] = $nivel === 5 ? $codigoUbicacion : 0;
        $updateData['codigoUbicacionN6'] = $nivel === 6 ? $codigoUbicacion : 0;

        $idProperty = 'idUbicacionN' . $nivel;

        $idEmplazamiento = $emplaObj->{$idProperty};
        $nombre = $emplaObj->descripcionUbicacion;
        $codigoUbicacion = (string) $emplaObj->codigoUbicacion;



        $updated = DB::table('inv_inventario')
            ->where('etiqueta', $etiqueta)
            ->where('id_ciclo', $ciclo_id)
            ->update($updateData);

        if ($updated === 0) {
            return response()->json([
                'status' => 'NOK',
                'message' => 'Error al actualizar los datos del inventario o no se realizó ningún cambio',
                'code' => 500
            ], 500);
        }

        $bien = Inventario::where('etiqueta', '=', $etiqueta)->first();

        $bien->fillCodeAndIDSEmplazamientos();

        return response()->json([
            [
                'id' => $idEmplazamiento,
                'ciclo_id' => $ciclo_id,
                'codigoUbicacion' => $codigoUbicacion,
                'nombre' => $nombre,
                'bien' => $bien
            ]
        ]);
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
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, int $id)
    {
        $codigo_ubicacion_n1 = $request->input('codigo_ubicacion_n1');
        $codigo_ubicacion_sub_nivel = $request->input('codigo_ubicacion_sub_nivel');
        $id_agenda = $request->input('id_agenda');

        $validatedData = $request->validate([
            'nombre_emplazamiento' => 'string|max:255',
            'ubicacion_emplazamiento' => 'string|max:255',
            'zona_id' => 'required|exists:ubicaciones_n1,idUbicacionN1',
            'id_agenda' => 'required|exists:ubicaciones_n1,idAgenda',
        ]);

        $updated = [];

        $emplaN1 = EmplazamientoN1::where('codigoUbicacion', $codigo_ubicacion_n1)
            ->where('idAgenda', $id_agenda)
            ->first();

        if ($emplaN1) {
            $emplaN1->descripcionUbicacion = $validatedData['nombre_emplazamiento'];
            $emplaN1->save();
            $updated['nivel1'] = $emplaN1;
        }

        $emplaSub = null;
        if (!empty($codigo_ubicacion_sub_nivel)) {
            $length = strlen($codigo_ubicacion_sub_nivel);

            if ($length >= 6) {
                $emplaSub = EmplazamientoN3::where('codigoUbicacion', $codigo_ubicacion_sub_nivel)
                    ->where('idAgenda', $id_agenda)
                    ->first();
            } elseif ($length >= 4) {
                $emplaSub = EmplazamientoN2::where('codigoUbicacion', $codigo_ubicacion_sub_nivel)
                    ->where('idAgenda', $id_agenda)
                    ->first();
            }

            if ($emplaSub) {
                $emplaSub->descripcionUbicacion = $validatedData['nombre_emplazamiento'];
                $emplaSub->save();
                $updated['subnivel'] = $emplaSub;
            }
        }

        $zona = ZonaPunto::find($validatedData['zona_id']);
        if ($zona) {
            $zona->descripcionUbicacion = $validatedData['ubicacion_emplazamiento'];
            $zona->save();
            $updated['zona'] = $zona;
        } else {
            return response()->json([
                'status' => 'NOK',
                'code' => 404,
                'message' => 'Zona no encontrada'
            ], 404);
        }

        return response()->json([
            'status' => 'OK',
            'message' => 'Emplazamiento y zona actualizados correctamente',
            'data' => $updated
        ], 200);
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
