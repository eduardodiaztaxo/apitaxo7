<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Inv_imagenes;
use App\Services\ImageService;
use App\Services\Imagenes\PictureSafinService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Intervention\Image\Encoders\WebpEncoder;

class ConvertImagesToWebp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:convert-to-webp {--limit=100 : Cantidad de imágenes a procesar por ejecución} {--table=all : Tabla a procesar (inv, crud o all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convierte imágenes JPG existentes en disco a formato WebP para ahorrar espacio y optimizar calidad.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $limit = $this->option('limit');
        $table = $this->option('table');

        if ($table === 'all' || $table === 'inv') {
            $this->processTable('inv_imagenes', $limit);
        }

        if ($table === 'all' || $table === 'crud') {
            $this->processTable('crud_activos_pictures', $limit);
        }

        return 0;
    }

    private function processTable(string $tableName, int $limit)
    {
        $this->info("Procesando tabla: $tableName...");

        $query = DB::table($tableName)
            ->where('picture', 'LIKE', '%.jpg')
            ->orderBy('id_proyecto'); // Agrupar por proyecto para optimizar rutas

        $total = $query->count();
        $this->info("Total de imágenes JPG encontradas: $total");

        $items = $query->take($limit)->get();
        $bar = $this->output->createProgressBar(count($items));

        foreach ($items as $item) {
            $this->convertItem($tableName, $item);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function convertItem(string $tableName, $item)
    {
        // Obtener nombre del cliente (necesario para la ruta del subdirectorio)
        // Nota: Asumimos que podemos obtenerlo del proyecto o que está estandarizado
        $cliente = $this->getCustomerName($tableName, $item);
        if (!$cliente) return;

        $oldName = $item->picture;
        $newName = pathinfo($oldName, PATHINFO_FILENAME) . '.webp';
        $subdir = PictureSafinService::getImgSubdir($cliente);
        
        $oldPath = $subdir . '/' . $oldName;
        $newPath = $subdir . '/' . $newName;

        // Intentar encontrar el archivo en los discos configurados
        $disk = null;
        if (Storage::disk('win_images')->exists($oldPath)) {
            $disk = 'win_images';
        } elseif (Storage::disk('taxoImages')->exists($oldPath)) {
            $disk = 'taxoImages';
        }

        if (!$disk) {
            return; // El archivo no existe físicamente
        }

        try {
            // 1. Leer y Convertir
            $content = Storage::disk($disk)->get($oldPath);
            $img = Image::read($content);

            // Redimensionar si es necesario (manteniendo consistencia con ImageService)
            if ($img->width() > 1024) {
                $img->scale(width: 1024);
            }

            $encoded = $img->encode(new WebpEncoder(quality: 65));

            // 2. Guardar nuevo WebP
            Storage::disk($disk)->put($newPath, $encoded);
            $newUrl = Storage::disk($disk)->url($newPath);

            // 3. Actualizar DB
            $updateData = [
                'picture' => $newName,
                'url_imagen' => $newUrl,
                'origen' => 'CRON_WEBP_CONVERSION'
            ];

            // Ajuste para diferentes nombres de columnas en las tablas
            if ($tableName === 'crud_activos_pictures') {
                DB::table($tableName)->where('id_foto', $item->id_foto)->update($updateData);
            } else {
                DB::table($tableName)->where('idLista', $item->idLista)->update($updateData);
            }

            // 4. Borrar Original para liberar espacio
            Storage::disk($disk)->delete($oldPath);

        } catch (\Exception $e) {
            $this->error("\nError procesando {$oldName}: " . $e->getMessage());
        }
    }

    private function getCustomerName(string $tableName, $item): ?string
    {
        // Esta es la parte que depende de tu estructura de datos. 
        // Normalmente el proyecto_id está ligado a un cliente.
        // Como solución rápida, buscaremos el nombre del cliente en la tabla de proyectos si existe.
        
        $proyectoId = $tableName === 'crud_activos_pictures' ? $item->idProyecto : $item->id_proyecto;
        
        // Buscamos un usuario que pertenezca a este proyecto para sacar su nombre_cliente
        $user = DB::table('sec_users_proyectos')
            ->join('sec_users', 'sec_users_proyectos.login', '=', 'sec_users.login')
            ->where('sec_users_proyectos.id_proyecto', $proyectoId)
            ->select('sec_users.nombre_cliente')
            ->first();

        return $user->nombre_cliente ?? null;
    }
}
