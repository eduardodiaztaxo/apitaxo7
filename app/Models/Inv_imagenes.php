<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inv_imagenes extends Model
{
    use HasFactory;
    protected $table = 'inv_imagenes';
    protected $primaryKey = 'idLista';
    public $incrementing = true;
    public $timestamps = true;


    public static function nextNameImageFile($proyecto_id, $etiqueta_nombre, $ext)
    {

        // 2. Preparamos la consulta para buscar el índice más alto actual
        // Buscamos: MAX( indice ) donde proyecto y etiqueta coincidan
        $max_indice = self::where('id_proyecto', $proyecto_id)
            ->where('etiqueta', $etiqueta_nombre)
            ->where('picture', 'LIKE', '%' . $etiqueta_nombre . '%')
            ->selectRaw("MAX(CAST(SUBSTRING_INDEX(picture, '_', -1) AS UNSIGNED)) as max_indice")
            ->value('max_indice');

        // 3. Obtenemos el resultado

        // 4. Lógica para determinar el siguiente índice
        if ($max_indice === null) {
            // Si es null, significa que no existen imágenes para este proyecto/etiqueta
            $nuevo_indice = 0;
        } else {
            // Si existe, le sumamos 1
            $nuevo_indice = $max_indice + 1;
        }

        // 5. Construimos el nombre final del archivo
        $nuevo_nombre = $proyecto_id . '_' . $etiqueta_nombre . '_' . $nuevo_indice . '.' . $ext;

        return $nuevo_nombre;
    }
}
