<?php

namespace App\Services;

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

        Storage::disk('public')->put(
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
}
