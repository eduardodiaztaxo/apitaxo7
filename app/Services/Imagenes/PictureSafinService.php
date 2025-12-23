<?php


namespace App\Services\Imagenes;

use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class PictureSafinService
{
    public static function getImgPath(): string
    {
        $disk = Storage::disk('taxoImages');

        $adapter = $disk->getAdapter(); // Get the adapter for the disk

        $storage_path = $adapter->getPathPrefix();

        if (substr($storage_path, -1) === '/' || substr($storage_path, -1) === '\\') {
            $storage_path = substr($storage_path, 0, -1);
        }

        return $storage_path;
    }
    public static function getImgSubdir(string $client_name): string
    {
        $cleanName = str_replace(' ', '_', $client_name);
        return "/" . $cleanName . "/img";
    }

    public static function nextNameImageFile($proyecto_id, $etiqueta_nombre, $ext)
    {
        // 2. Preparamos la consulta para buscar el índice más alto actual
        // Buscamos: MAX( indice ) donde proyecto y etiqueta coincidan
        $sql = "SELECT MAX(CAST(SUBSTRING_INDEX(picture, '_', -1) AS UNSIGNED)) as max_indice 
            FROM inv_imagenes 
            WHERE id_proyecto = '$proyecto_id' 
            AND etiqueta = '" . $etiqueta_nombre . "' AND picture LIKE '%" . $etiqueta_nombre . "%' ";

        $result = DB::select($sql);

        $max_indice = $result[0]->max_indice;

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
