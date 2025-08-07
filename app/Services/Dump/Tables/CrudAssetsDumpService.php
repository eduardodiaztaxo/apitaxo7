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

        // Decodificar el JSON a un arreglo asociativo
        $data = json_decode($jsonContent);

        if (isset($data->status) && $data->status !== 'OK') {
            return;
        }


        $this->insert($data);
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
                usuario TEXT NOT NULL,
                tipoAlta TEXT NOT NULL,
                categoriaN3 TEXT NOT NULL,
                id_familia INTEGER NOT NULL,
                id_grupo INTEGER NOT NULL,
                descripcionCategoria TEXT NOT NULL,
                descripcionFamilia TEXT NOT NULL,
                foto1 TEXT,
                valorCompra REAL NOT NULL,
                valorFinal TEXT NOT NULL,
                fechaCompra TEXT NOT NULL,
                vidaUtil TEXT NOT NULL,
                fechaActivacion TEXT,
                cantidad TEXT NOT NULL,
                serie TEXT NOT NULL,
                etiqueta TEXT NOT NULL,
                opcional2 TEXT,
                nombreActivo TEXT,
                descripcionActivo TEXT NOT NULL,
                marca TEXT NOT NULL,
                modelo TEXT NOT NULL,
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
                status INTEGER NOT NULL,
                estadoBien TEXT,
                ciclo_id INTEGER NOT NULL,
                fotoUrl TEXT NOT NULL,
                foto_url TEXT,
                codigo_activo TEXT NOT NULL,
                codigo_cliente TEXT NOT NULL,
                plazoGarantia TEXT,
                fechaDevengo TEXT,
                numeroDevengo TEXT NOT NULL,
                numeroResolucion TEXT,
                fechaResolucion TEXT,
                depreciable TEXT NOT NULL,
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
                descripcionTipo TEXT NOT NULL,
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
                ':usuario' => $asset->usuario,
                ':tipoAlta' => $asset->tipoAlta,
                ':categoriaN3' => $asset->categoriaN3,
                ':id_familia' => $asset->id_familia,
                ':id_grupo' => $asset->id_grupo,
                ':descripcionCategoria' => $asset->descripcionCategoria,
                ':descripcionFamilia' => $asset->descripcionFamilia,
                ':foto1' => $asset->foto1,
                ':valorCompra' => $asset->valorCompra,
                ':valorFinal' => $asset->valorFinal,
                ':fechaCompra' => $asset->fechaCompra,
                ':vidaUtil' => $asset->vidaUtil,
                ':fechaActivacion' => $asset->fechaActivacion,
                ':cantidad' => $asset->cantidad,
                ':serie' => $asset->serie,
                ':etiqueta' => $asset->etiqueta,
                ':opcional2' => $asset->opcional2,
                ':nombreActivo' => $asset->nombreActivo,
                ':descripcionActivo' => isset($asset->descripcionActivo) ? $asset->descripcionActivo : '',
                ':marca' => $asset->marca,
                ':modelo' => $asset->modelo,
                ':material' => $asset->material,
                ':forma' => $asset->forma,
                ':estadoConservacion' => $asset->estadoConservacion,
                ':apoyaBrazosRuedas' => $asset->apoyaBrazosRuedas,
                ':color' => $asset->color,
                ':estadoOperacional' => $asset->estadoOperacional,
                ':texto_abierto_1' => $asset->texto_abierto_1,
                ':texto_abierto_2' => $asset->texto_abierto_2,
                ':texto_abierto_3' => $asset->texto_abierto_3,
                ':texto_abierto_4' => $asset->texto_abierto_4,
                ':texto_abierto_5' => $asset->texto_abierto_5,
                ':eficiencia' => $asset->eficiencia,
                ':capacidad' => $asset->capacidad,
                ':tipoTrabajo' => $asset->tipoTrabajo,
                ':status' => $asset->status,
                ':estadoBien' => $asset->estadoBien,
                ':ciclo_id' => $this->cycle,
                ':fotoUrl' => $asset->fotoUrl ? $asset->fotoUrl : '',
                ':foto_url' => isset($asset->foto_url) ? $asset->foto_url : '',
                ':codigo_activo' => $asset->codigo_activo,
                ':codigo_cliente' => $asset->codigo_cliente,
                ':plazoGarantia' => $asset->plazoGarantia,
                ':fechaDevengo' => $asset->fechaDevengo,
                ':numeroDevengo' => $asset->numeroDevengo,
                ':numeroResolucion' => $asset->numeroResolucion,
                ':fechaResolucion' => $asset->fechaResolucion,
                ':depreciable' => $asset->depreciable,
                ':responsable' => $asset->responsable,
                ':idUbicacionGeografica' => $asset->idUbicacionGeografica,
                ':organica_n1' => $asset->organica_n1,
                ':ubicacionOrganicaN1' => $asset->ubicacionOrganicaN1,
                ':organica_n2' => $asset->organica_n2,
                ':ubicacionOrganicaN2' => $asset->ubicacionOrganicaN2,
                ':ubicacionOrganicaN3' => $asset->ubicacionOrganicaN3,
                ':observacion' => $asset->observacion,
                ':latitud' => $asset->latitud,
                ':longitud' => $asset->longitud,
                ':descripcionTipo' => $asset->descripcionTipo ? $asset->descripcionTipo : '',
                ':status_scan_id' => isset($asset->status_scan_id) ? $asset->status_scan_id : 0,
                ':status_scan_name' => isset($asset->status_scan_name) ? $asset->status_scan_name : '',
                ':status_scan_extra_class' => isset($asset->status_scan_extra_class) ? $asset->status_scan_extra_class : ''
            ]);
        }
    }
}
