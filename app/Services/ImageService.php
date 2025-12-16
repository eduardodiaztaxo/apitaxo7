<?php

namespace App\Services;

use App\Services\Imagenes\PictureSafinService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Laravel\Facades\Image as Image;

class ImageService
{


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
}
