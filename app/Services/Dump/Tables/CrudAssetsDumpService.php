<?php

namespace App\Services\Dump\Tables;

use App\Http\Controllers\Api\V1\CiclosController;
use App\Http\Controllers\Api\V1\CrudActivoController;
use App\Services\ActivoService;
use App\Services\Dump\Tables\DumpSQLiteInterface;
use App\Services\ImageService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use PDO;

class CrudAssetsDumpService implements DumpSQLiteInterface
{


    /**
     * @var \PDO|null PDO connection instance
     */
    protected $pdo = null;

    /**
     * @var int Cycle number
     */
    protected $cycle = 0;


    public function __construct(PDO $pdo, int $cycle = 0)
    {
        $this->pdo = $pdo;

        $this->cycle = $cycle;
    }

    /**
     * Run the assets dump from the controller.
     *
     * This method creates the ciclos table and inserts assets data into it from controller.
     *
     * @return void
     */
    public function runFromController(): void
    {

        $this->createTable();



        $request = new \Illuminate\Http\Request();
        $request->setMethod('GET');

        $activoService = new ActivoService();
        $imageService = new ImageService();

        $crudActivosCtrl = new CrudActivoController($activoService, $imageService);

        $response = $crudActivosCtrl->showAllAssetsByCycleCats($request, $this->cycle);

        $jsonContent = $response->getContent();

        // Decodificar el JSON
        $data = json_decode($jsonContent);

        if (isset($data->status) && $data->status !== 'OK') {
            return;
        }

        // Si los datos están envueltos en "data", extraerlos
        $assets = isset($data->data) ? $data->data : $data;

        $this->insert($assets);
    }


    /**
     * Create the assets table if it does not exist.
     *
     * This method creates the assets table with the specified columns and their data types.
     *
     * @return void
     */
    public function createTable(): void
    {


        // Create "assets" table
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS assets (
                idActivo INTEGER PRIMARY KEY,
                usuario TEXT,
                tipoAlta TEXT,
                categoriaN3 TEXT,
                id_familia INTEGER,
                id_grupo INTEGER,
                descripcionCategoria TEXT,
                descripcionFamilia TEXT,
                foto1 TEXT,
                valorCompra REAL,
                valorFinal TEXT,
                fechaCompra TEXT,
                vidaUtil TEXT,
                fechaActivacion TEXT,
                cantidad TEXT,
                serie TEXT,
                etiqueta TEXT,
                opcional2 TEXT,
                nombreActivo TEXT,
                descripcionActivo TEXT,
                marca TEXT,
                modelo TEXT,
                material TEXT,
                forma TEXT,
                estadoConservacion INTEGER,
                apoyaBrazosRuedas TEXT,
                color TEXT,
                estadoOperacional INTEGER,
                texto_abierto_1 TEXT,
                texto_abierto_2 TEXT,
                texto_abierto_3 TEXT,
                texto_abierto_4 TEXT,
                texto_abierto_5 TEXT,
                eficiencia TEXT,
                capacidad TEXT,
                tipoTrabajo TEXT,
                cargaTrabajo TEXT,
                condicionAmbiental TEXT,
                status INTEGER,
                estadoBien TEXT,
                ciclo_id INTEGER,
                fotoUrl TEXT,
                foto_url TEXT,
                codigo_activo TEXT,
                codigo_cliente TEXT,
                plazoGarantia TEXT,
                fechaDevengo TEXT,
                numeroDevengo TEXT,
                numeroResolucion TEXT,
                fechaResolucion TEXT,
                depreciable TEXT,
                responsable TEXT,
                idUbicacionGeografica INTEGER,
                organica_n1 TEXT,
                ubicacionOrganicaN1 TEXT,
                organica_n2 TEXT,
                ubicacionOrganicaN2 TEXT,
                ubicacionOrganicaN3 TEXT,
                observacion TEXT,
                latitud TEXT,
                longitud TEXT,
                descripcionTipo TEXT,
                status_scan_id INTEGER,
                status_scan_name TEXT,
                status_scan_extra_class TEXT

            );
        ");
    }

    /**
     * Insert assets into the assets table.
     *
     * @param \Illuminate\Http\Resources\Json\AnonymousResourceCollection $cycles Array of cycle objects to insert.
     * @return void
     */
    public function insert(array|AnonymousResourceCollection $assets): void
    {
        // Insertar datos
        $stmt = $this->pdo->prepare("
            INSERT INTO assets (
                idActivo,
                usuario,
                tipoAlta,
                categoriaN3,
                id_familia,
                id_grupo,
                descripcionCategoria,
                descripcionFamilia,
                foto1,
                valorCompra,
                valorFinal,
                fechaCompra,
                vidaUtil,
                fechaActivacion,
                cantidad,
                serie,
                etiqueta,
                opcional2,
                nombreActivo,
                descripcionActivo,
                marca,
                modelo,
                material,
                forma,
                estadoConservacion,
                apoyaBrazosRuedas,
                color,
                estadoOperacional,
                texto_abierto_1,
                texto_abierto_2,
                texto_abierto_3,
                texto_abierto_4,
                texto_abierto_5,
                eficiencia,
                capacidad,
                tipoTrabajo,
                cargaTrabajo,
                condicionAmbiental,
                status,
                estadoBien,
                ciclo_id,
                fotoUrl,
                foto_url,
                codigo_activo,
                codigo_cliente,
                plazoGarantia,
                fechaDevengo,
                numeroDevengo,
                numeroResolucion,
                fechaResolucion,
                depreciable,
                responsable,
                idUbicacionGeografica,
                organica_n1,
                ubicacionOrganicaN1,
                organica_n2,
                ubicacionOrganicaN2,
                ubicacionOrganicaN3,
                observacion,
                latitud,
                longitud,
                descripcionTipo,
                status_scan_id,
                status_scan_name,
                status_scan_extra_class
            )
            VALUES (
                :idActivo,
                :usuario,
                :tipoAlta,
                :categoriaN3,
                :id_familia,
                :id_grupo,
                :descripcionCategoria,
                :descripcionFamilia,
                :foto1,
                :valorCompra,
                :valorFinal,
                :fechaCompra,
                :vidaUtil,
                :fechaActivacion,
                :cantidad,
                :serie,
                :etiqueta,
                :opcional2,
                :nombreActivo,
                :descripcionActivo,
                :marca,
                :modelo,
                :material,
                :forma,
                :estadoConservacion,
                :apoyaBrazosRuedas,
                :color,
                :estadoOperacional,
                :texto_abierto_1,
                :texto_abierto_2,
                :texto_abierto_3,
                :texto_abierto_4,
                :texto_abierto_5,
                :eficiencia,
                :capacidad,
                :tipoTrabajo,
                :cargaTrabajo,
                :condicionAmbiental,
                :status,
                :estadoBien,
                :ciclo_id,
                :fotoUrl,
                :foto_url,
                :codigo_activo,
                :codigo_cliente,
                :plazoGarantia,
                :fechaDevengo,
                :numeroDevengo,
                :numeroResolucion,
                :fechaResolucion,
                :depreciable,
                :responsable,
                :idUbicacionGeografica,
                :organica_n1,
                :ubicacionOrganicaN1,
                :organica_n2,
                :ubicacionOrganicaN2,
                :ubicacionOrganicaN3,
                :observacion,
                :latitud,
                :longitud,
                :descripcionTipo,
                :status_scan_id,
                :status_scan_name,
                :status_scan_extra_class
            )
        ");



        foreach ($assets as $asset) {

            //$asset = json_decode($asset->toJson());


            $stmt->execute([
                ':idActivo' => $asset->idActivo,
                ':usuario' => $asset->usuario ?? null,
                ':tipoAlta' => $asset->tipoAlta ?? null,
                ':categoriaN3' => $asset->categoriaN3 ?? null,
                ':id_familia' => $asset->id_familia ?? 0,
                ':id_grupo' => $asset->id_grupo ?? 0,
                ':descripcionCategoria' => $asset->descripcionCategoria ?? '',
                ':descripcionFamilia' => $asset->descripcionFamilia ?? '',
                ':foto1' => $asset->foto1 ?? null,
                ':valorCompra' => $asset->valorCompra ?? 0,
                ':valorFinal' => $asset->valorFinal ?? '',
                ':fechaCompra' => $asset->fechaCompra ?? '',
                ':vidaUtil' => $asset->vidaUtil ?? '',
                ':fechaActivacion' => $asset->fechaActivacion ?? null,
                ':cantidad' => $asset->cantidad ?? '',
                ':serie' => $asset->serie ?? '',
                ':etiqueta' => $asset->etiqueta ?? '',
                ':opcional2' => $asset->opcional2 ?? null,
                ':nombreActivo' => $asset->nombreActivo ?? null,
                ':descripcionActivo' => isset($asset->descripcionActivo) ? $asset->descripcionActivo : '',
                ':marca' => $asset->marca ?? '',
                ':modelo' => $asset->modelo ?? '',
                ':material' => $asset->material ?? null,
                ':forma' => $asset->forma ?? null,
                ':estadoConservacion' => $asset->estadoConservacion ?? null,
                ':apoyaBrazosRuedas' => $asset->apoyaBrazosRuedas ?? null,
                ':color' => $asset->color ?? null,
                ':estadoOperacional' => $asset->estadoOperacional ?? null,
                ':texto_abierto_1' => $asset->texto_abierto_1 ?? null,
                ':texto_abierto_2' => $asset->texto_abierto_2 ?? null,
                ':texto_abierto_3' => $asset->texto_abierto_3 ?? null,
                ':texto_abierto_4' => $asset->texto_abierto_4 ?? null,
                ':texto_abierto_5' => $asset->texto_abierto_5 ?? null,
                ':eficiencia' => $asset->eficiencia ?? null,
                ':capacidad' => $asset->capacidad ?? null,
                ':tipoTrabajo' => $asset->tipoTrabajo ?? null,
                ':cargaTrabajo' => $asset->cargaTrabajo ?? null,
                ':condicionAmbiental' => $asset->condicionAmbiental ?? null,
                ':status' => $asset->status ?? 0,
                ':estadoBien' => $asset->estadoBien ?? null,
                ':ciclo_id' => $this->cycle,
                ':fotoUrl' => $asset->fotoUrl ? $asset->fotoUrl : '',
                ':foto_url' => isset($asset->foto_url) ? $asset->foto_url : '',
                ':codigo_activo' => $asset->codigo_activo ?? '',
                ':codigo_cliente' => $asset->codigo_cliente ?? '',
                ':plazoGarantia' => $asset->plazoGarantia ?? null,
                ':fechaDevengo' => $asset->fechaDevengo ?? null,
                ':numeroDevengo' => $asset->numeroDevengo ?? '',
                ':numeroResolucion' => $asset->numeroResolucion ?? null,
                ':fechaResolucion' => $asset->fechaResolucion ?? null,
                ':depreciable' => $asset->depreciable ?? '',
                ':responsable' => $asset->responsable ?? null,
                ':idUbicacionGeografica' => $asset->idUbicacionGeografica ?? 0,
                ':organica_n1' => $asset->organica_n1 ?? null,
                ':ubicacionOrganicaN1' => $asset->ubicacionOrganicaN1 ?? null,
                ':organica_n2' => $asset->organica_n2 ?? null,
                ':ubicacionOrganicaN2' => $asset->ubicacionOrganicaN2 ?? null,
                ':ubicacionOrganicaN3' => $asset->ubicacionOrganicaN3 ?? null,
                ':observacion' => $asset->observacion ?? null,
                ':latitud' => $asset->latitud ?? null,
                ':longitud' => $asset->longitud ?? null,
                ':descripcionTipo' => $asset->descripcionTipo ?? '',
                ':status_scan_id' => isset($asset->status_scan_id) ? $asset->status_scan_id : 0,
                ':status_scan_name' => isset($asset->status_scan_name) ? $asset->status_scan_name : '',
                ':status_scan_extra_class' => isset($asset->status_scan_extra_class) ? $asset->status_scan_extra_class : ''
            ]);
        }
    }
}
