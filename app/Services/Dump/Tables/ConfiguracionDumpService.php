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

    public function __construct(PDO $pdo, string $codigo_grupo = '')
    {
        $this->pdo = $pdo;
        $this->codigo_grupo = $codigo_grupo;
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

        $response = $datsdActivosCtrl->configuracionOffline([$this->codigo_grupo]);

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
            label_texto_abierto_2 TEXT
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
                label_texto_abierto_2
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
                :label_texto_abierto_2
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
            ]);
        }
    }
}
