<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class InvConfigService
{


    public static function getOpenedTextConfigInput(int $id_grupo): array
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
        FROM inv_atributos WHERE id_atributo IN (27,28,29,30,31,34,35,36,37,38) 
        AND id_validacion <> 0 AND id_grupo = ?";

        $inputs = DB::select($sql, [$id_grupo]);

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
