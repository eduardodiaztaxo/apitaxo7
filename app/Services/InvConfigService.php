<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class InvConfigService
{


    public static function getOpenedTextConfigInput(int $id_grupo, int $id_proyecto): array
    {
        $sql = "SELECT 
        id_lista AS id_lista,
        CASE
            WHEN id_atributo = 27 THEN 'texto_abierto_1'
            WHEN id_atributo = 28 THEN 'texto_abierto_2'
            WHEN id_atributo = 29 THEN 'texto_abierto_3'
            WHEN id_atributo = 30 THEN 'texto_abierto_4'
            WHEN id_atributo = 31 THEN 'texto_abierto_5'
            WHEN id_atributo = 34 THEN 'texto_abierto_6'
            WHEN id_atributo = 35 THEN 'texto_abierto_7'
            WHEN id_atributo = 36 THEN 'texto_abierto_8'
            WHEN id_atributo = 37 THEN 'texto_abierto_9'
            WHEN id_atributo = 38 THEN 'texto_abierto_10'
            WHEN id_atributo = 39 THEN 'texto_abierto_11'
            WHEN id_atributo = 40 THEN 'texto_abierto_12'
            WHEN id_atributo = 41 THEN 'texto_abierto_13'
            WHEN id_atributo = 42 THEN 'texto_abierto_14'
            WHEN id_atributo = 43 THEN 'texto_abierto_15'
            WHEN id_atributo = 44 THEN 'texto_abierto_16'
            WHEN id_atributo = 45 THEN 'texto_abierto_17'
            WHEN id_atributo = 46 THEN 'texto_abierto_18'
            WHEN id_atributo = 47 THEN 'texto_abierto_19'
            WHEN id_atributo = 48 THEN 'texto_abierto_20'
            WHEN id_atributo = 49 THEN 'texto_abierto_21'
            WHEN id_atributo = 50 THEN 'texto_abierto_22'
            WHEN id_atributo = 51 THEN 'texto_abierto_23'
            WHEN id_atributo = 52 THEN 'texto_abierto_24'
            WHEN id_atributo = 53 THEN 'texto_abierto_25'
            WHEN id_atributo = 54 THEN 'texto_abierto_26'
            WHEN id_atributo = 55 THEN 'texto_abierto_27'
            WHEN id_atributo = 56 THEN 'texto_abierto_28'
            WHEN id_atributo = 57 THEN 'texto_abierto_29'
            WHEN id_atributo = 58 THEN 'texto_abierto_30'
            WHEN id_atributo = 59 THEN 'texto_abierto_31'
            WHEN id_atributo = 60 THEN 'texto_abierto_32'
            WHEN id_atributo = 61 THEN 'texto_abierto_33'
            WHEN id_atributo = 62 THEN 'texto_abierto_34'
            WHEN id_atributo = 63 THEN 'texto_abierto_35'
            WHEN id_atributo = 64 THEN 'texto_abierto_36'
            WHEN id_atributo = 65 THEN 'texto_abierto_37'
            WHEN id_atributo = 66 THEN 'texto_abierto_38'
            WHEN id_atributo = 67 THEN 'texto_abierto_39'
            WHEN id_atributo = 68 THEN 'texto_abierto_40'
            WHEN id_atributo = 69 THEN 'texto_abierto_41'
            WHEN id_atributo = 70 THEN 'texto_abierto_42'
            WHEN id_atributo = 71 THEN 'texto_abierto_43'
            WHEN id_atributo = 72 THEN 'texto_abierto_44'
            WHEN id_atributo = 73 THEN 'texto_abierto_45'
            WHEN id_atributo = 74 THEN 'texto_abierto_46'
            WHEN id_atributo = 75 THEN 'texto_abierto_47'
            WHEN id_atributo = 76 THEN 'texto_abierto_48'
            WHEN id_atributo = 77 THEN 'texto_abierto_49'
            WHEN id_atributo = 78 THEN 'texto_abierto_50'
            ELSE ''
        END AS input_name,	
        type_input AS `type`,	
        CASE
            WHEN id_validacion = 1 THEN TRUE 
            ELSE FALSE
        END AS required,
        CASE
            WHEN id_tipo_dato = 1 THEN 'number' 
            WHEN id_tipo_dato = 2 THEN 'text'
            ELSE 'text'
        END AS datatype,
        valor_minimo AS `min`,
        valor_maximo AS `max`,
        label_input AS label
        FROM inv_atributos WHERE id_atributo IN (27,28,29,30,31,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63,64,65,66,67,68,69,70,71,72,73,74,75,76,77,78) AND id_validacion != 0 AND id_grupo = ? AND id_proyecto = ?";

        $inputs = DB::select($sql, [$id_grupo, $id_proyecto]);

        $inputs_map = [];
        foreach ($inputs as $input) {

            $options = DB::select("SELECT texto FROM `inv_atributos_input_options` WHERE id_lista = ? ", [$input->id_lista]);

            $options = collect($options);

            $inputs_map[] = [
                'type'          => $input->type,
                'input_name'    => $input->input_name,
                'required'      => $input->required,
                'min'           => $input->min,
                'max'           => $input->max,
                'label'         => $input->label,
                'options'       => $options->pluck('texto')
            ];
        }

        return $inputs_map;
    }
}
