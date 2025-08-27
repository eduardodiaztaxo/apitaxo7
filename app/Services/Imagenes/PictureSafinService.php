<?php


namespace App\Services\Imagenes;

use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

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

}