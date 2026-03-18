<?php

namespace App\Services;

use App\Models\Inv_imagenes;
use App\Services\Imagenes\PictureSafinService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Laravel\Facades\Image as Image;

class ImageService
{


    /**
     * Internal method to process and encode image.
     *
     * @param  \Illuminate\Http\UploadedFile $uploadedImage
     * @param  string $format 'webp' or 'jpg'
     * @param  int $quality
     * @return array ['content' => encoded_string, 'extension' => string]
     */
    private static function processImage(UploadedFile $uploadedImage, string $format = 'webp', int $quality = 65): array
    {
        $img = Image::read($uploadedImage->getRealPath());

        // Redimensionar si el ancho supera los 1024px
        if ($img->width() > 1024) {
            $img->scale(width: 1024);
        }

        // Seleccionar encoder según formato
        $encoder = ($format === 'webp')
            ? new \Intervention\Image\Encoders\WebpEncoder(quality: $quality)
            : new \Intervention\Image\Encoders\JpegEncoder(quality: $quality);

        return [
            'content' => $img->encode($encoder),
            'extension' => $format
        ];
    }

    /**
     * Resize, optimize and save images in public disk.
     */
    public function optimizeImageAndSave(UploadedFile $uploadedImage, string $subdir, string $imageName): string
    {
        $processed = self::processImage($uploadedImage, 'webp', 65);
        $path = $subdir . "/" . $imageName . "." . $processed['extension'];

        Storage::disk('public')->put($path, $processed['content']);

        return $path;
    }

    /**
     * Alias of optimizeImageAndSave for backward compatibility.
     */
    public function optimizeImageinv(UploadedFile $uploadedImage, string $subdir, string $imageName): string
    {
        return $this->optimizeImageAndSave($uploadedImage, $subdir, $imageName);
    }

    /**
     * Save optimized image in main disk or second disk if fails.
     */
    public static function saveImageInMainOrSecondDisk(UploadedFile $file, string $customer_name, string $namefile): string|null
    {
        $url = null;

        try {
            // OPTIMIZACIÓN ANTES DE GUARDAR (Soluciona el problema de >1MB)
            $processed = self::processImage($file, 'webp', 65);
            $content = $processed['content'];

            // Asegurarnos de que el nombre del archivo refleje la extensión real
            $extension = pathinfo($namefile, PATHINFO_EXTENSION);
            if ($extension !== $processed['extension']) {
                $namefile = pathinfo($namefile, PATHINFO_FILENAME) . '.' . $processed['extension'];
            }

            $subdir = PictureSafinService::getImgSubdir($customer_name);
            $path = $subdir . '/' . $namefile;

            // Intentar en disco principal
            Storage::disk('win_images')->put($path, $content);
            $url = Storage::disk('win_images')->url($path);

        } catch (\Exception $e) {

            try {
                // Fallback al disco secundario
                Storage::disk('taxoImages')->put($path, $content);
                $url = Storage::disk('taxoImages')->url($path);
            } catch (\Exception $e) {
                // Loguear error si ambos fallan
                \Illuminate\Support\Facades\Log::error("Error guardando imagen: " . $e->getMessage());
            }
        }

        return $url;
    }

    /**
     * Delete image in main disk or second disk if exists.
     *
     * @param  string  $customer_name   the customer name to build the subdir
     * @param  string  $namefile
     * @return bool true if deleted, false otherwise
     */
    public static function deleteImageInMainOrSecondDisk(string $customer_name, string $namefile): bool
    {
        $deleted = false;




        try {

            $path = PictureSafinService::getImgSubdir($customer_name) . '/' . $namefile;

            $deleted = Storage::disk('win_images')->delete($path);
        } catch (\Exception $e) {

            try {

                $path = PictureSafinService::getImgSubdir($customer_name) . '/' . $namefile;

                $deleted = Storage::disk('taxoImages')->delete($path);
            } catch (\Exception $e) {
            }
        }

        return $deleted;
    }

    /**
     * Update names of image files when etiqueta field changes.
     *
     * @param  string  $etiqueta
     * @param  string  $old_etiqueta
     * @param  string  $proyecto_id
     * @param  string  $customer_name
     * @return void
     */
    public static function updateNameFilesWhenEtiquetaFieldChanges(string $etiqueta, string $old_etiqueta, string $proyecto_id, string $customer_name): void
    {
        //

        Inv_imagenes::where('id_proyecto', $proyecto_id)
            ->where('etiqueta', $old_etiqueta)
            ->get()
            ->each(function ($image) use ($etiqueta, $proyecto_id, $customer_name) {

                $oldNameFile = $image->picture;

                if (self::checkIfImageExistsInMainOrSecondDisk($customer_name, $oldNameFile) === false) {
                    // if the old file does not exist, skip renaming
                    return;
                }

                $extension = pathinfo($oldNameFile, PATHINFO_EXTENSION);

                $newNameFile = PictureSafinService::nextNameImageFile($proyecto_id, $etiqueta, $extension);

                // Rename the image in storage

                $newUrl = ImageService::renameImageInMainOrSecondDisk($newNameFile, $oldNameFile, $customer_name);

                if ($newUrl) {
                    // Update database record
                    $image->picture = $newNameFile;
                    $image->url_imagen = $newUrl;
                    $image->etiqueta = $etiqueta;
                    $image->origen = 'SAFIN_APP_ETIQUETA_EDITADA';
                    //$image->updated_at = now();
                    $image->save();
                }
            });
    }


    /**
     * Update names of image files when etiqueta field does not match with image name.
     *
     * @param  string  $proyecto_id
     * @param  string  $customer_name
     * @return void
     */
    public static function updateFileNamesWhenEtiquetaFieldNoMatchWithImageName(string $proyecto_id, string $customer_name): int
    {
        //

        $quantity = 0;

        $images = Inv_imagenes::where('id_proyecto', '=', $proyecto_id)
            ->whereRaw("picture NOT LIKE CONCAT('%', etiqueta, '%')")
            ->get();

        foreach ($images as $image) {

            $oldNameFile = $image->picture;

            if (self::checkIfImageExistsInMainOrSecondDisk($customer_name, $oldNameFile) === false) {
                // if the old file does not exist, skip renaming
                continue;
            }

            $extension = pathinfo($oldNameFile, PATHINFO_EXTENSION);

            $newNameFile = PictureSafinService::nextNameImageFile($proyecto_id, $image->etiqueta, $extension);

            // Rename the image in storage

            $newUrl = ImageService::renameImageInMainOrSecondDisk($newNameFile, $oldNameFile, $customer_name);

            if ($newUrl) {
                // Update database record
                $image->picture = $newNameFile;
                $image->url_imagen = $newUrl;
                $image->origen = 'SAFIN_APP_ETIQUETA_EDITADA';
                //$image->updated_at = now();
                $image->save();

                $quantity++;
            }
        }

        return $quantity;
    }


    /**
     * rename image in main disk or second disk if fails.
     *
     * @param  string  $new_namefile
     * @param  string  $old_namefile 
     * @param  string  $customer_name   the customer name to build the subdir
     * @return string|null the URL of the saved image or null if both saves fail
     */
    public static function renameImageInMainOrSecondDisk(string $new_namefile, string $old_namefile, string $customer_name): string|null
    {
        $url = null;

        try {

            $old_path = PictureSafinService::getImgSubdir($customer_name) . '/' . $old_namefile;
            $new_path = PictureSafinService::getImgSubdir($customer_name) . '/' . $new_namefile;

            Storage::disk('win_images')->move($old_path, $new_path);

            $url = Storage::disk('win_images')->url($new_path);
        } catch (\Exception $e) {

            try {

                $old_path = PictureSafinService::getImgSubdir($customer_name) . '/' . $old_namefile;
                $new_path = PictureSafinService::getImgSubdir($customer_name) . '/' . $new_namefile;

                Storage::disk('taxoImages')->move($old_path, $new_path);

                $url = Storage::disk('taxoImages')->url($new_path);
            } catch (\Exception $e) {
            }
        }

        return $url;
    }


    /**
     * Move images from second disk to main disk when they have been saved in second disk.
     *
     * @param  string  $proyecto_id
     * @param  string  $customer_name
     * @param  int     $max_images
     * @return array ['success' => int, 'failed' => int]
     */
    public static function moveToMainDiskWhenImagesHaveBeenSavedInSecondDisk(string $proyecto_id, string $customer_name, int $max_images = 0): array
    {
        
        $urlSecondDisk = Storage::disk('taxoImages')->url('/');
    
        $imagesQuery = Inv_imagenes::where('id_proyecto', '=', $proyecto_id)
            ->where('url_imagen', 'LIKE', ''.$urlSecondDisk.'%');

        $imagesQuery = $max_images > 0 ? $imagesQuery->take($max_images) : $imagesQuery;

        $images = $imagesQuery->get();

        $result = ['success' => 0, 'failed' => 0];

        foreach ($images as $image) {

            $exists = self::checkIfImageExistsInMainDisk($customer_name, $image->picture);

            $moved = false;

            if(!$exists){
                $moved = self::moveImageFromSecondDiskToMainDisk($customer_name, $image->picture);
            }

            

            if ($moved || $exists) {
                $new_path = PictureSafinService::getImgSubdir($customer_name) . '/' . $image->picture;
                $image->url_imagen = Storage::disk('win_images')->url($new_path);
                $image->url_picture = Storage::disk('win_images')->url(PictureSafinService::getImgSubdir($customer_name) . '/');
                $image->save();

                $result['success'] += 1;
            } else {
                // log error or take appropriate action
                $result['failed'] += 1;
            }
            
        }

        return $result;
    }

    
    /**
     * Move image from second disk to main disk.
     * 
     * @param  string  $customer_name   the customer name to build the subdir
     * @param  string  $namefile
     * @return bool true if moved, false otherwise
     */
    public static function moveImageFromSecondDiskToMainDisk(string $customer_name, string $namefile): bool
    {
        $moved = false;

        $old_path = PictureSafinService::getImgSubdir($customer_name) . '/' . $namefile;
        $new_path = PictureSafinService::getImgSubdir($customer_name) . '/' . $namefile;

        try {

            $content = Storage::disk('taxoImages')->get($old_path);

            $moved = Storage::disk('win_images')->put($new_path, $content);
              

           
            
        } catch (\Exception $e) {
            $moved = false;
        }

        try {
            if ($moved) {
                Storage::disk('taxoImages')->delete($old_path);
            }
        } catch (\Exception $e) {
        }

        return $moved;
    }

    /**
     * Check if image exists in main disk or second disk.
     *
     * @param  string  $customer_name   the customer name to build the subdir
     * @param  string  $namefile
     * @return bool true if exists, false otherwise
     */
    public static function checkIfImageExistsInMainOrSecondDisk(string $customer_name, string $namefile): bool
    {
        $exists = false;

        try {

            $path = PictureSafinService::getImgSubdir($customer_name) . '/' . $namefile;

            $exists = Storage::disk('win_images')->exists($path);
        } catch (\Exception $e) {

            try {

                $path = PictureSafinService::getImgSubdir($customer_name) . '/' . $namefile;

                $exists = Storage::disk('taxoImages')->exists($path);
            } catch (\Exception $e) {
            }
        }

        return $exists;
    }


    /**
     * Check if image exists in main disk.
     *
     * @param  string  $customer_name   the customer name to build the subdir
     * @param  string  $namefile
     * @return bool true if exists, false otherwise
     */
    public static function checkIfImageExistsInMainDisk(string $customer_name, string $namefile): bool
    {
        $exists = false;

        try {

            $path = PictureSafinService::getImgSubdir($customer_name) . '/' . $namefile;

            $exists = Storage::disk('win_images')->exists($path);

        } catch (\Exception $e) {

            $exists = false;
        }

        return $exists;
    }
}
