<?php

namespace App\Services;

use App\Models\Inv_imagenes;
use App\Models\Inv_imagenes_thumbnails;
use App\Services\Imagenes\PictureSafinService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Laravel\Facades\Image as Image;

class ImageService
{

    const INV_IMG_ID = 'INV_IMG_ID';
    /**
     * Resize, optimize and save images.
     *
     * @param  \Illuminate\Http\UploadedFile $uploadedImage 
     * @param  string  $subdir
     * @param  string  $imageName
     * @return string
     */
    public function optimizeImageAndSave(UploadedFile $uploadedImage, string $subdir, string $imageName): string
    {

        $path = $uploadedImage->getRealPath();

        $img = Image::read($path);

        $ext = 'jpg';

        if ($img->width() > 1024) {

            $height = round($img->height() * 1024 / $img->width());

            $img->resize(1024, $height, function ($constraint) {
                $constraint->aspectRatio();
            });
        }


        $encode = $img->encode(new JpegEncoder(quality: 10));

        //sobre escribir sobre la imagen subida
        //$img->save($uploadedImage->getRealPath());

        $path = $subdir . "/" . $imageName . "." . $ext;

        Storage::disk('public')->put( //dico esta especificado en filesystem en public 
            $path,
            $encode
        );
        // guardar imagen
        // $path = Storage::putFileAs(
        //     $subdir,
        //     $uploadedImage->getRealPath(),
        //     $imageName . "." . $ext
        // );

        return $path;
    }
    /**
     * delete image.
     *
     * @param  string  $path
     * @return bool
     */
    public function deleteImage(string $path)
    {

        return Storage::disk('public')->delete($path);
    }


    public function optimizeImageinv(UploadedFile $uploadedImage, string $subdir, string $imageName): string
    {

        $path = $uploadedImage->getRealPath();

        $img = Image::read($path);

        $ext = 'jpg';

        if ($img->width() > 1024) {

            $height = round($img->height() * 1024 / $img->width());

            $img->resize(1024, $height, function ($constraint) {
                $constraint->aspectRatio();
            });
        }


        $encode = $img->encode(new JpegEncoder(quality: 10));

        $path = $subdir . "/" . $imageName . "." . $ext;

        Storage::disk('public')->put( //dico esta especificado en filesystem en public 
            $path,
            $encode
        );

        return $path;
    }

    /**
     * Save image in main disk or second disk if fails.
     *
     * @param  \Illuminate\Http\UploadedFile $file 
     * @param  string  $customer_name   the customer name to build the subdir
     * @param  string  $namefile
     * @return string|null the URL of the saved image or null if both saves fail
     */
    public static function saveImageInMainOrSecondDisk(UploadedFile $file, string $customer_name, string $namefile): string|null
    {
        $url = null;

        try {

            $path = $file->storeAs(
                PictureSafinService::getImgSubdir($customer_name),
                $namefile,
                'win_images'
            );

            $url = Storage::disk('win_images')->url($path);
        } catch (\Exception $e) {

            try {

                $path = $file->storeAs(
                    PictureSafinService::getImgSubdir($customer_name),
                    $namefile,
                    'taxoImages'
                );

                $url = Storage::disk('taxoImages')->url($path);
            } catch (\Exception $e) {
            }
        }


        return $url;
    }

    /**
     * Save only the thumbnail version of an image in the secondary disk.
     *
     * The thumbnail is compressed to webp, reduced to 96px width and stored
     * exclusively in taxoImages under the customer image subdirectory.
     *
     * @param  \Illuminate\Http\UploadedFile $file
     * @param  string  $customer_name   the customer name to build the subdir
     * @param  string  $namefile
     * @return string|null the URL of the saved thumbnail or null if save fails
     */
    public static function saveThumbnailInSecondDisk(UploadedFile $file, string $customer_name, string $namefile): string|null
    {
        try {
            $thumbName = 'thumb_' . pathinfo($namefile, PATHINFO_FILENAME) . '.webp';
            $thumbPath = PictureSafinService::getImgSubdir($customer_name) . '/' . $thumbName;

            $img = Image::read($file->getRealPath());
            $img->scale(width: 96);

            $encodedThumb = $img->encode(new WebpEncoder(quality: 60))->toString();

            if (Storage::disk('taxoImages')->put($thumbPath, $encodedThumb)) {
                return Storage::disk('taxoImages')->url($thumbPath);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error guardando miniatura: ' . $e->getMessage());
        }

        return null;
    }

    public static function createThumbnail(UploadedFile $file, string $customer_name, string $namefile): Model|null
    {
        $path = self::saveThumbnailInSecondDisk($file, $customer_name, $namefile);
        if (!$path) {
            return null;
        }
        $thumbnail = self::saveThumbnailInDB($path, $customer_name, $namefile);
        return $thumbnail;
    }

    public static function saveThumbnailInDB(string $url, string $customer_name, string $namefile): Model|null
    {
        try {
            $parentImage = Inv_imagenes::where('picture', $namefile)
                ->orderByDesc('idLista')
                ->first();

            if (!$parentImage) {
                \Illuminate\Support\Facades\Log::warning('No se encontró la imagen principal para guardar la miniatura', [
                    'namefile' => $namefile,
                    'customer_name' => $customer_name,
                    'url' => $url,
                ]);

                return null;
            }

            $thumbName = 'thumb_' . pathinfo($namefile, PATHINFO_FILENAME) . '.webp';
            $thumbPath = PictureSafinService::getImgSubdir($customer_name) . '/' . $thumbName;

            $thumb = Inv_imagenes_thumbnails::where('id_img', $parentImage->id_img)
                ->where('etiqueta', $parentImage->etiqueta)
                ->first();

            if ($thumb && $thumb->picture && $thumb->picture !== $thumbName) {
                $oldThumbPath = PictureSafinService::getImgSubdir($customer_name) . '/' . $thumb->picture;
                Storage::disk('taxoImages')->delete($oldThumbPath);
            }

            $thumb = $thumb ?? new Inv_imagenes_thumbnails();
            $thumb->id_img = $parentImage->id_img;
            $thumb->origen = $parentImage->origen;
            $thumb->etiqueta = $parentImage->etiqueta;
            $thumb->picture = $thumbName;
            $thumb->url_imagen = $url;
            $thumb->url_picture = dirname($url) . '/';
            $thumb->id_proyecto = $parentImage->id_proyecto;
            $thumb->q_descargas = $parentImage->q_descargas;
            $thumb->save();

            return $thumb;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error guardando miniatura en DB: ' . $e->getMessage(), [
                'namefile' => $namefile,
                'customer_name' => $customer_name,
                'url' => $url,
            ]);

            return null;
        }
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
            ->where('url_imagen', 'LIKE', '' . $urlSecondDisk . '%');

        $imagesQuery = $max_images > 0 ? $imagesQuery->take($max_images) : $imagesQuery;

        $images = $imagesQuery->get();

        $result = ['success' => 0, 'failed' => 0];

        foreach ($images as $image) {

            $exists = self::checkIfImageExistsInMainDisk($customer_name, $image->picture);

            $moved = false;

            if (!$exists) {
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

    /**
     * Create next value in sequence table for inv_img_id if not exist.
     *
     * @return void
     */
    public static function createNextValInvImgIfNotExist()
    {

        $secuence = DB::table('sequence')->where('name', '=', self::INV_IMG_ID)->first();
        if (!$secuence) {
            $id_img = DB::table('inv_imagenes')->max('id_img') + 1;
            DB::insert('INSERT INTO `sequence` (`name`,`cur_value`) VALUES (?, ?)', [self::INV_IMG_ID, $id_img]);
        }
    }

    /**
     * Get next value in sequence table for inv_img_id.
     *
     * @return int next value
     */
    public static function nextValInvImg()
    {
        self::createNextValInvImgIfNotExist();

        DB::statement(
            'UPDATE `sequence` SET `cur_value` = LAST_INSERT_ID(`cur_value`) + `increment` WHERE `name` = ?',
            [self::INV_IMG_ID]
        );

        $nextVal = DB::selectOne('SELECT LAST_INSERT_ID() as next_val');

        return (int) $nextVal->next_val;
    }
}
