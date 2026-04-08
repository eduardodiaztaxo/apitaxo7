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
            conf_responsable INTEGER,
            required_responsible_signature INTEGER,
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
            INSERT OR REPLACE INTO configuracion (
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
                conf_responsable,
                required_responsible_signature,
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
                :conf_responsable,
                :required_responsible_signature,
                :custom_fields
            )
        ");

        foreach ($config as $conf) {

            $stmt->execute([
                ':id_grupo' => $conf->id_grupo ?? 0,
                ':conf_marca' => $conf->conf_marca ?? 0,
                ':conf_modelo' => $conf->conf_modelo ?? 0,
                ':tipo_dato_mod' => $conf->tipo_dato_mod ?? 0,
                ':lench_Min_mod' => $conf->lench_Min_mod ?? 0,
                ':lench_Max_mod' => $conf->lench_Max_mod ?? 0,
                ':conf_capacidad' => $conf->conf_capacidad ?? 0,
                ':tipo_dato_cap' => $conf->tipo_dato_cap ?? 0,
                ':lench_Min_cap' => $conf->lench_Min_cap ?? 0,
                ':lench_Max_cap' => $conf->lench_Max_cap ?? 0,
                ':conf_material' => $conf->conf_material ?? 0,
                ':conf_forma' => $conf->conf_forma ?? 0,
                ':conf_estado' => $conf->conf_estado ?? 0,
                ':conf_estado_operacional' => $conf->conf_estado_operacional ?? 0,
                ':conf_serie' => $conf->conf_serie ?? 0,
                ':tipo_dato_serie' => $conf->tipo_dato_serie ?? 0,
                ':lench_Min_serie' => $conf->lench_Min_serie ?? 0,
                ':lench_Max_serie' => $conf->lench_Max_serie ?? 0,
                ':conf_color' => $conf->conf_color ?? 0,
                ':conf_estado_conservacion' => $conf->conf_estado_conservacion ?? 0,
                ':conf_tipo_trabajo' => $conf->conf_tipo_trabajo ?? 0,
                ':conf_carga_trabajo' => $conf->conf_carga_trabajo ?? 0,
                ':conf_condicion_ambiental' => $conf->conf_condicion_ambiental ?? 0,
                ':lench_Min_etiqueta' => $conf->lench_Min_etiqueta ?? 0,
                ':lench_Max_etiqueta' => $conf->lench_Max_etiqueta ?? 0,
                ':tipo_etiqueta' => $conf->tipo_etiqueta ?? null,
                ':conf_latitud' => $conf->conf_latitud ?? 0,
                ':conf_longitud' => $conf->conf_longitud ?? 0,
                ':conf_padre' => $conf->conf_padre ?? 0,

                // edualejandro - Eficiencia
                ':conf_eficiencia' => $conf->conf_eficiencia ?? 0,
                ':tipo_dato_eficiencia' => $conf->tipo_dato_eficiencia ?? 0,
                ':lench_Min_eficiencia' => $conf->lench_Min_eficiencia ?? 0,
                ':lench_Max_eficiencia' => $conf->lench_Max_eficiencia ?? 0,

                // edualejandro - Responsable y firma
                ':conf_responsable' => $conf->conf_responsable ?? 0,
                //':required_responsible_signature' => $conf->required_responsible_signature ?? 0,
                ':required_responsible_signature' => 0,
                // edualejandro
                ':custom_fields' => $conf->custom_fields ?? null
            ]);
        }
    }
}
