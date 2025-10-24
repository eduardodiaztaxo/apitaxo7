<?php

namespace App\Services\Dump\Tables;

use App\Http\Controllers\Api\V1\InventariosOfflineController;
use App\Services\Dump\Tables\DumpSQLiteInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use PDO;

class ConfiguracionDumpService implements DumpSQLiteInterface
{


    /**
     * @var \PDO|null PDO connection instance
     */
    protected $pdo = null;
    

    /**
     * @var int codigo_grupo string
     */

    protected $codigo_grupo = '';
    protected $cycle = 0;

    public function __construct(PDO $pdo, string $codigo_grupo = '', int $cycle = 0)
    {
        $this->pdo = $pdo;
        $this->codigo_grupo = $codigo_grupo;
        $this->cycle = $cycle;
    }
    /**
     * Run dump from the controller.
     *
     * This method creates the ciclos table and inserts data into it from controller.
     *
     * @return void
     */
    public function runFromController(): void
    {

        $this->createTable();

        $request = new \Illuminate\Http\Request();
        $request->setMethod('GET');

        $datsdActivosCtrl = new InventariosOfflineController();

        $response = $datsdActivosCtrl->configuracionOffline([$this->codigo_grupo], $this->cycle);

        $jsonContent = $response->getContent();

        $data = json_decode($jsonContent);

        if (isset($data->status) && $data->status !== 'OK') {
            return;
        }


        $this->insert($data);
    }


    /**
     * Create table if it does not exist.
     *
     * This method creates table with the specified columns and their data types.
     *
     * @return void
     */
    public function createTable(): void
    {
        $this->pdo->exec("
        CREATE TABLE IF NOT EXISTS configuracion (
            id_grupo INTEGER,
            conf_marca INTEGER,
            conf_modelo INTEGER,
            tipo_dato_mod INTEGER,
            lench_Min_mod INTEGER,
            lench_Max_mod INTEGER,
            conf_capacidad INTEGER,
            tipo_dato_cap INTEGER,
            lench_Min_cap INTEGER,
            lench_Max_cap INTEGER,
            conf_material INTEGER,
            conf_forma INTEGER,
            conf_estado INTEGER,
            conf_estado_operacional INTEGER,
            conf_serie INTEGER,
            tipo_dato_serie INTEGER,
            lench_Min_serie INTEGER,
            lench_Max_serie INTEGER,
            conf_color INTEGER,
            conf_estado_conservacion INTEGER,
            conf_tipo_trabajo INTEGER,
            conf_carga_trabajo INTEGER,
            conf_condicion_ambiental INTEGER,
            lench_Min_etiqueta INTEGER,
            lench_Max_etiqueta INTEGER,
            tipo_etiqueta TEXT,
            conf_latitud INTEGER,
            conf_longitud INTEGER,
            conf_padre INTEGER,
            -- edualejandro 
            conf_eficiencia INTEGER,
            tipo_dato_eficiencia INTEGER,
            lench_Min_eficiencia INTEGER,
            lench_Max_eficiencia INTEGER,
            -- edualejandro
            conf_texto_abierto_1 INTEGER,
            tipo_dato_texto_abierto_1 INTEGER,
            lench_Min_texto_abierto_1 INTEGER,
            lench_Max_texto_abierto_1 INTEGER,
            label_texto_abierto_1 TEXT,
            -- edualejandro
            conf_texto_abierto_2 INTEGER,
            tipo_dato_texto_abierto_2 INTEGER,
            lench_Min_texto_abierto_2 INTEGER,
            lench_Max_texto_abierto_2 INTEGER,
            label_texto_abierto_2 TEXT,
            -- edualejandro
            conf_texto_abierto_3 INTEGER,
            tipo_dato_texto_abierto_3 INTEGER,
            lench_Min_texto_abierto_3 INTEGER,
            lench_Max_texto_abierto_3 INTEGER,
            label_texto_abierto_3 TEXT,
            -- edualejandro
            conf_texto_abierto_4 INTEGER,
            tipo_dato_texto_abierto_4 INTEGER,
            lench_Min_texto_abierto_4 INTEGER,
            lench_Max_texto_abierto_4 INTEGER,
            label_texto_abierto_4 TEXT,
            -- edualejandro
            conf_texto_abierto_5 INTEGER,
            tipo_dato_texto_abierto_5 INTEGER,
            lench_Min_texto_abierto_5 INTEGER,
            lench_Max_texto_abierto_5 INTEGER,
            label_texto_abierto_5 TEXT,
            conf_fotos INTEGER,
            conf_range_polygonal INTEGER,
            -- edualejandro
            conf_texto_abierto_6 INTEGER,
            tipo_dato_texto_abierto_6 INTEGER,
            lench_Min_texto_abierto_6 INTEGER,
            lench_Max_texto_abierto_6 INTEGER,
            label_texto_abierto_6 TEXT,
            -- edualejandro
            conf_texto_abierto_7 INTEGER,
            tipo_dato_texto_abierto_7 INTEGER,
            lench_Min_texto_abierto_7 INTEGER,
            lench_Max_texto_abierto_7 INTEGER,
            label_texto_abierto_7 TEXT,
            -- edualejandro
            conf_texto_abierto_8 INTEGER,
            tipo_dato_texto_abierto_8 INTEGER,
            lench_Min_texto_abierto_8 INTEGER,
            lench_Max_texto_abierto_8 INTEGER,
            label_texto_abierto_8 TEXT,
            -- edualejandro
            conf_texto_abierto_9 INTEGER,
            tipo_dato_texto_abierto_9 INTEGER,
            lench_Min_texto_abierto_9 INTEGER,
            lench_Max_texto_abierto_9 INTEGER,
            label_texto_abierto_9 TEXT,
            -- edualejandro
            conf_texto_abierto_10 INTEGER,
            tipo_dato_texto_abierto_10 INTEGER,
            lench_Min_texto_abierto_10 INTEGER,
            lench_Max_texto_abierto_10 INTEGER,
            label_texto_abierto_10 TEXT,

            -- edualejandro
            custom_fields TEXT

        )
    ");
    }


    /**
     *
     * @param \Illuminate\Http\Resources\Json\AnonymousResourceCollection $cycles Array of cycle objects to insert.
     * @return void
     */
    public function insert(array|AnonymousResourceCollection $config): void
    {
        // Insertar datos
        $stmt = $this->pdo->prepare("
            INSERT INTO configuracion (
                id_grupo,
                conf_marca,
                conf_modelo,
                tipo_dato_mod,
                lench_Min_mod,
                lench_Max_mod,
                conf_capacidad,
                tipo_dato_cap,
                lench_Min_cap,
                lench_Max_cap,
                conf_material,
                conf_forma,
                conf_estado,
                conf_estado_operacional,
                conf_serie,
                tipo_dato_serie,
                lench_Min_serie,
                lench_Max_serie,
                conf_color,
                conf_estado_conservacion,
                conf_tipo_trabajo,
                conf_carga_trabajo,
                conf_condicion_ambiental,
                lench_Min_etiqueta,
                lench_Max_etiqueta,
                tipo_etiqueta,
                conf_latitud,
                conf_longitud,
                conf_padre,
                -- edualejandro 
                conf_eficiencia,
                tipo_dato_eficiencia,
                lench_Min_eficiencia,
                lench_Max_eficiencia,
                -- edualejandro
                conf_texto_abierto_1,
                tipo_dato_texto_abierto_1,
                lench_Min_texto_abierto_1,
                lench_Max_texto_abierto_1,
                label_texto_abierto_1,
                -- edualejandro
                conf_texto_abierto_2,
                tipo_dato_texto_abierto_2,
                lench_Min_texto_abierto_2,
                lench_Max_texto_abierto_2,
                label_texto_abierto_2,
                -- edualejandro
                conf_texto_abierto_3,
                tipo_dato_texto_abierto_3,
                lench_Min_texto_abierto_3,
                lench_Max_texto_abierto_3,
                label_texto_abierto_3,
                -- edualejandro
                conf_texto_abierto_4,
                tipo_dato_texto_abierto_4,
                lench_Min_texto_abierto_4,
                lench_Max_texto_abierto_4,
                label_texto_abierto_4,
                -- edualejandro
                conf_texto_abierto_5,
                tipo_dato_texto_abierto_5,
                lench_Min_texto_abierto_5,
                lench_Max_texto_abierto_5,
                label_texto_abierto_5,
                conf_fotos,
                conf_range_polygonal,
                -- edualejandro
                conf_texto_abierto_6,
                tipo_dato_texto_abierto_6,
                lench_Min_texto_abierto_6,
                lench_Max_texto_abierto_6,
                label_texto_abierto_6,
                -- edualejandro
                conf_texto_abierto_7,
                tipo_dato_texto_abierto_7,
                lench_Min_texto_abierto_7,
                lench_Max_texto_abierto_7,
                label_texto_abierto_7,
                -- edualejandro
                conf_texto_abierto_8,
                tipo_dato_texto_abierto_8,
                lench_Min_texto_abierto_8,
                lench_Max_texto_abierto_8,
                label_texto_abierto_8,
                -- edualejandro
                conf_texto_abierto_9,
                tipo_dato_texto_abierto_9,
                lench_Min_texto_abierto_9,
                lench_Max_texto_abierto_9,
                label_texto_abierto_9,
                -- edualejandro
                conf_texto_abierto_10,
                tipo_dato_texto_abierto_10,
                lench_Min_texto_abierto_10,
                lench_Max_texto_abierto_10,
                label_texto_abierto_10,
                -- edualejandro
                custom_fields
            )
            VALUES (
                :id_grupo,
                :conf_marca,
                :conf_modelo,
                :tipo_dato_mod,
                :lench_Min_mod,
                :lench_Max_mod,
                :conf_capacidad,
                :tipo_dato_cap,
                :lench_Min_cap,
                :lench_Max_cap,
                :conf_material,
                :conf_forma,
                :conf_estado,
                :conf_estado_operacional,
                :conf_serie,
                :tipo_dato_serie,
                :lench_Min_serie,
                :lench_Max_serie,
                :conf_color,
                :conf_estado_conservacion,
                :conf_tipo_trabajo,
                :conf_carga_trabajo,
                :conf_condicion_ambiental,
                :lench_Min_etiqueta,
                :lench_Max_etiqueta,
                :tipo_etiqueta,
                :conf_latitud,
                :conf_longitud,
                :conf_padre,
                -- edualejandro
                :conf_eficiencia,
                :tipo_dato_eficiencia,
                :lench_Min_eficiencia,
                :lench_Max_eficiencia,
                -- edualejandro
                :conf_texto_abierto_1,
                :tipo_dato_texto_abierto_1,
                :lench_Min_texto_abierto_1,
                :lench_Max_texto_abierto_1,
                :label_texto_abierto_1,
                -- edualejandro
                :conf_texto_abierto_2,
                :tipo_dato_texto_abierto_2,
                :lench_Min_texto_abierto_2,
                :lench_Max_texto_abierto_2,
                :label_texto_abierto_2,
                -- edualejandro
                :conf_texto_abierto_3,
                :tipo_dato_texto_abierto_3,
                :lench_Min_texto_abierto_3,
                :lench_Max_texto_abierto_3,
                :label_texto_abierto_3,
                -- edualejandro
                :conf_texto_abierto_4,
                :tipo_dato_texto_abierto_4,
                :lench_Min_texto_abierto_4,
                :lench_Max_texto_abierto_4,
                :label_texto_abierto_4,
                -- edualejandro
                :conf_texto_abierto_5,
                :tipo_dato_texto_abierto_5,
                :lench_Min_texto_abierto_5,
                :lench_Max_texto_abierto_5,
                :label_texto_abierto_5,
                :conf_fotos,
                :conf_range_polygonal,
                -- edualejandro
                :conf_texto_abierto_6,
                :tipo_dato_texto_abierto_6,
                :lench_Min_texto_abierto_6,
                :lench_Max_texto_abierto_6,
                :label_texto_abierto_6,
                -- edualejandro
                :conf_texto_abierto_7,
                :tipo_dato_texto_abierto_7,
                :lench_Min_texto_abierto_7,
                :lench_Max_texto_abierto_7,
                :label_texto_abierto_7,
                -- edualejandro
                :conf_texto_abierto_8,
                :tipo_dato_texto_abierto_8,
                :lench_Min_texto_abierto_8,
                :lench_Max_texto_abierto_8,
                :label_texto_abierto_8,
                -- edualejandro
                :conf_texto_abierto_9,
                :tipo_dato_texto_abierto_9,
                :lench_Min_texto_abierto_9,
                :lench_Max_texto_abierto_9,
                :label_texto_abierto_9,
                -- edualejandro
                :conf_texto_abierto_10,
                :tipo_dato_texto_abierto_10,
                :lench_Min_texto_abierto_10,
                :lench_Max_texto_abierto_10,
                :label_texto_abierto_10,
                -- edualejandro
                :custom_fields
            )
        ");

        foreach ($config as $conf) {

            $stmt->execute([
                ':id_grupo' => $conf->id_grupo,
                ':conf_marca' => $conf->conf_marca,
                ':conf_modelo' => $conf->conf_modelo,
                ':tipo_dato_mod' => $conf->tipo_dato_mod,
                ':lench_Min_mod' => $conf->lench_Min_mod,
                ':lench_Max_mod' => $conf->lench_Max_mod,
                ':conf_capacidad' => $conf->conf_capacidad,
                ':tipo_dato_cap' => $conf->tipo_dato_cap,
                ':lench_Min_cap' => $conf->lench_Min_cap,
                ':lench_Max_cap' => $conf->lench_Max_cap,
                ':conf_material' => $conf->conf_material,
                ':conf_forma' => $conf->conf_forma,
                ':conf_estado' => $conf->conf_estado,
                ':conf_estado_operacional' => $conf->conf_estado_operacional,
                ':conf_serie' => $conf->conf_serie,
                ':tipo_dato_serie' => $conf->tipo_dato_serie,
                ':lench_Min_serie' => $conf->lench_Min_serie,
                ':lench_Max_serie' => $conf->lench_Max_serie,
                ':conf_color' => $conf->conf_color,
                ':conf_estado_conservacion' => $conf->conf_estado_conservacion,
                ':conf_tipo_trabajo' => $conf->conf_tipo_trabajo,
                ':conf_carga_trabajo' => $conf->conf_carga_trabajo,
                ':conf_condicion_ambiental' => $conf->conf_condicion_ambiental,
                ':lench_Min_etiqueta' => $conf->lench_Min_etiqueta,
                ':lench_Max_etiqueta' => $conf->lench_Max_etiqueta,
                ':tipo_etiqueta' => $conf->tipo_etiqueta,
                ':conf_latitud' => $conf->conf_latitud,
                ':conf_longitud' => $conf->conf_longitud,
                ':conf_padre' => $conf->conf_padre,
                // edualejandro
                ':conf_eficiencia' => $conf->conf_eficiencia,
                ':tipo_dato_eficiencia' => $conf->tipo_dato_eficiencia,
                ':lench_Min_eficiencia' => $conf->lench_Min_eficiencia,
                ':lench_Max_eficiencia' => $conf->lench_Max_eficiencia,
                // edualejandro
                ':conf_texto_abierto_1' => $conf->conf_texto_abierto_1,
                ':tipo_dato_texto_abierto_1' => $conf->tipo_dato_texto_abierto_1,
                ':lench_Min_texto_abierto_1' => $conf->lench_Min_texto_abierto_1,
                ':lench_Max_texto_abierto_1' => $conf->lench_Max_texto_abierto_1,
                ':label_texto_abierto_1' => $conf->label_texto_abierto_1,
                // edualejandro
                ':conf_texto_abierto_2' => $conf->conf_texto_abierto_2,
                ':tipo_dato_texto_abierto_2' => $conf->tipo_dato_texto_abierto_2,
                ':lench_Min_texto_abierto_2' => $conf->lench_Min_texto_abierto_2,
                ':lench_Max_texto_abierto_2' => $conf->lench_Max_texto_abierto_2,
                ':label_texto_abierto_2' => $conf->label_texto_abierto_2,
                // edualejandro
                ':conf_texto_abierto_3' => $conf->conf_texto_abierto_3,
                ':tipo_dato_texto_abierto_3' => $conf->tipo_dato_texto_abierto_3,
                ':lench_Min_texto_abierto_3' => $conf->lench_Min_texto_abierto_3,
                ':lench_Max_texto_abierto_3' => $conf->lench_Max_texto_abierto_3,
                ':label_texto_abierto_3' => $conf->label_texto_abierto_3,
                // edualejandro
                ':conf_texto_abierto_4' => $conf->conf_texto_abierto_4,
                ':tipo_dato_texto_abierto_4' => $conf->tipo_dato_texto_abierto_4,
                ':lench_Min_texto_abierto_4' => $conf->lench_Min_texto_abierto_4,
                ':lench_Max_texto_abierto_4' => $conf->lench_Max_texto_abierto_4,
                ':label_texto_abierto_4' => $conf->label_texto_abierto_4,
                // edualejandro
                ':conf_texto_abierto_5' => $conf->conf_texto_abierto_5,
                ':tipo_dato_texto_abierto_5' => $conf->tipo_dato_texto_abierto_5,
                ':lench_Min_texto_abierto_5' => $conf->lench_Min_texto_abierto_5,
                ':lench_Max_texto_abierto_5' => $conf->lench_Max_texto_abierto_5,
                ':label_texto_abierto_5' => $conf->label_texto_abierto_5,
                ':conf_fotos' => $conf->conf_fotos,
                ':conf_range_polygonal' => $conf->conf_range_polygonal,
                // edualejandro
                ':conf_texto_abierto_6' => $conf->conf_texto_abierto_6,
                ':tipo_dato_texto_abierto_6' => $conf->tipo_dato_texto_abierto_6,
                ':lench_Min_texto_abierto_6' => $conf->lench_Min_texto_abierto_6,
                ':lench_Max_texto_abierto_6' => $conf->lench_Max_texto_abierto_6,
                ':label_texto_abierto_6' => $conf->label_texto_abierto_6,
                // edualejandro
                ':conf_texto_abierto_7' => $conf->conf_texto_abierto_7,
                ':tipo_dato_texto_abierto_7' => $conf->tipo_dato_texto_abierto_7,
                ':lench_Min_texto_abierto_7' => $conf->lench_Min_texto_abierto_7,
                ':lench_Max_texto_abierto_7' => $conf->lench_Max_texto_abierto_7,
                ':label_texto_abierto_7' => $conf->label_texto_abierto_7,
                // edualejandro
                ':conf_texto_abierto_8' => $conf->conf_texto_abierto_8,
                ':tipo_dato_texto_abierto_8' => $conf->tipo_dato_texto_abierto_8,
                ':lench_Min_texto_abierto_8' => $conf->lench_Min_texto_abierto_8,
                ':lench_Max_texto_abierto_8' => $conf->lench_Max_texto_abierto_8,
                ':label_texto_abierto_8' => $conf->label_texto_abierto_8,
                // edualejandro
                ':conf_texto_abierto_9' => $conf->conf_texto_abierto_9,
                ':tipo_dato_texto_abierto_9' => $conf->tipo_dato_texto_abierto_9,
                ':lench_Min_texto_abierto_9' => $conf->lench_Min_texto_abierto_9,
                ':lench_Max_texto_abierto_9' => $conf->lench_Max_texto_abierto_9,
                ':label_texto_abierto_9' => $conf->label_texto_abierto_9,
                // edualejandro
                ':conf_texto_abierto_10' => $conf->conf_texto_abierto_10,
                ':tipo_dato_texto_abierto_10' => $conf->tipo_dato_texto_abierto_10,
                ':lench_Min_texto_abierto_10' => $conf->lench_Min_texto_abierto_10,
                ':lench_Max_texto_abierto_10' => $conf->lench_Max_texto_abierto_10,
                ':label_texto_abierto_10' => $conf->label_texto_abierto_10,
                //edualejandro
                ':custom_fields' => $conf->custom_fields
            ]);
        }
    }
}
