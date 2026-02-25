<?php

namespace App\Console\Commands;

use App\Services\ImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MoveImagesFromSecondToMainDisk extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:move-to-main-disk {--limit=} {--debug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move images from second to main disk. Frecuently some images are saved in the second disk by mistake or not available the main disk.';

    protected $connections = [
        [
            'conn_field' => 'mysql_lascondes_cert',
            'project_id' => 1004,
            'customer_name' => 'LASCONDES_CERT',
        ],
        [
            'conn_field' => 'mysql_lascondes',
            'project_id' => 9967,
            'customer_name' => 'LAS CONDES',
        ]
    ];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Start moving images for all clients...');
        $this->newLine();

        $limit = $this->option('limit');
        $limit = $limit ? (int)$limit : 1000;
        $debug = $this->option('debug');

        $totalSuccess = 0;
        $totalFailed = 0;
        $totalSkipped = 0;

        $errorLogPath = storage_path('logs/migrations/migration_log_' . date('Y-m-d') . '.txt');
        if (!file_exists(dirname($errorLogPath))) {
            mkdir(dirname($errorLogPath), 0755, true);
        }
        
        // ← LEER contenido existente del archivo
        $existingContent = '';
        if (file_exists($errorLogPath)) {
            $existingContent = file_get_contents($errorLogPath);
        }
        
        // ← BUFFER para nuevos logs (se escribirá al inicio)
        $newLogs = "";
        $hasErrors = false;
        $hasSuccess = false;

        Log::info('=== INICIO MOVIMIENTO DE IMÁGENES ===', ['limit' => $limit]);

        foreach ($this->connections as $index => $connection) {
            try {
                $clientNumber = $index + 1;
                $totalClients = count($this->connections);

                $this->info("Processing client {$clientNumber}/{$totalClients}: {$connection['customer_name']}");
                
                Log::info("Procesando cliente", [
                    'customer' => $connection['customer_name'],
                    'project_id' => $connection['project_id']
                ]);

                // Verificar conexión existe en BD
                $result = DB::connection('mysql_auth')->select(
                    'SELECT project_name, project_id FROM api_project_conn WHERE conn_field = ?',
                    [$connection['conn_field']]
                );

                if (empty($result)) {
                    $this->error("Connection '{$connection['conn_field']}' not found in database. Skipping...");
                    
                    $hasErrors = true;
                    $newLogs .= "[" . date('Y-m-d H:i:s') . "] ERROR - {$connection['customer_name']}\n" .
                        "  Motivo: Conexión no encontrada en la base de datos\n" .
                        "  Conexión: {$connection['conn_field']}\n\n";
                    
                    Log::warning("Conexión no encontrada en BD", ['conn_field' => $connection['conn_field']]);
                    continue;
                }

                $dbProjectName = $result[0]->project_name;
                $dbProjectId = $result[0]->project_id;

                if ($dbProjectId != $connection['project_id']) {
                    $this->error("project_id mismatch. Database: {$dbProjectId} vs Config: {$connection['project_id']}. Skipping...");
                    
                    $hasErrors = true;
                    $newLogs .= "[" . date('Y-m-d H:i:s') . "] ERROR - {$connection['customer_name']}\n" .
                        "  Motivo: project_id no coincide\n" .
                        "  BD: {$dbProjectId} | Configuración: {$connection['project_id']}\n\n";
                    
                    Log::error("project_id no coincide", [
                        'db' => $dbProjectId,
                        'config' => $connection['project_id']
                    ]);
                    continue;
                }

                if ($dbProjectName !== $connection['customer_name']) {
                    $this->warn("Warning: customer_name mismatch. Database: '{$dbProjectName}' vs Config: '{$connection['customer_name']}'. Using database value.");
                    $connection['customer_name'] = $dbProjectName;
                }

                $this->line("Connection verified in database");

                if ($debug) {
                    $totalInDb = DB::connection($connection['conn_field'])
                        ->table('inv_imagenes')
                        ->where('id_proyecto', $connection['project_id'])
                        ->count();
                    
                    $this->comment("Total images in project: {$totalInDb}");
                    $this->comment("Processing limit: {$limit}");
                }

                DB::setDefaultConnection($connection['conn_field']);

                // Ejecutar el servicio
                $results = ImageService::moveToMainDiskWhenImagesHaveBeenSavedInSecondDisk(
                    $connection['project_id'],
                    $connection['customer_name'],
                    $limit
                );

                // Acumular resultados
                $totalSuccess += $results['success'];
                $totalFailed += $results['failed'];
                $totalSkipped += $results['skipped'] ?? 0;

                // ← REGISTRAR archivos faltantes
                if (!empty($results['missing_files'])) {
                    $hasErrors = true;
                    
                    $missingList = "";
                    foreach ($results['missing_files'] as $missingFile) {
                        $missingList .= "    - {$missingFile}\n";
                    }
                    
                    $newLogs .= "[" . date('Y-m-d H:i:s') . "] ARCHIVOS FALTANTES - {$connection['customer_name']}\n" .
                        "  Project ID: {$connection['project_id']}\n" .
                        "  Total archivos omitidos: " . count($results['missing_files']) . "\n" .
                        "  Archivos que están en BD pero NO existen físicamente:\n" .
                        $missingList . "\n";
                }

                // ← REGISTRAR errores al mover
                if ($results['failed'] > 0) {
                    $hasErrors = true;
                    $newLogs .= "[" . date('Y-m-d H:i:s') . "] ERRORES AL MOVER - {$connection['customer_name']}\n" .
                        "  Project ID: {$connection['project_id']}\n" .
                        "  Movidas: {$results['success']} | Fallidas: {$results['failed']} | Omitidas: " . ($results['skipped'] ?? 0) . "\n\n";
                }

                // ← REGISTRAR ÉXITOS (cuando todo sale bien)
                if ($results['success'] > 0 && $results['failed'] == 0 && empty($results['missing_files'])) {
                    $hasSuccess = true;
                    $newLogs .= "[" . date('Y-m-d H:i:s') . "] ÉXITO - {$connection['customer_name']}\n" .
                        "  Project ID: {$connection['project_id']}\n" .
                        "  Imágenes movidas: {$results['success']}\n" .
                        "  Omitidas: " . ($results['skipped'] ?? 0) . "\n\n";
                }

                // ← REGISTRAR ÉXITO PARCIAL (movió algunas pero hubo problemas)
                if ($results['success'] > 0 && ($results['failed'] > 0 || !empty($results['missing_files']))) {
                    $hasSuccess = true;
                    $newLogs .= "[" . date('Y-m-d H:i:s') . "] ÉXITO PARCIAL - {$connection['customer_name']}\n" .
                        "  Project ID: {$connection['project_id']}\n" .
                        "  Movidas: {$results['success']} | Fallidas: {$results['failed']} | Omitidas: " . ($results['skipped'] ?? 0) . "\n\n";
                }

                // ← REGISTRAR cuando NO hay imágenes para mover
                if ($results['success'] == 0 && $results['failed'] == 0 && $results['skipped'] == 0) {
                    $newLogs .= "[" . date('Y-m-d H:i:s') . "] ℹSIN IMÁGENES - {$connection['customer_name']}\n" .
                        "  Project ID: {$connection['project_id']}\n" .
                        "  No hay imágenes pendientes de mover\n\n";
                }

                $this->line("  Moved: {$results['success']} | Failed: {$results['failed']} | Skipped: " . ($results['skipped'] ?? 0));
                
                Log::info("Cliente procesado", [
                    'customer' => $connection['customer_name'],
                    'moved' => $results['success'],
                    'failed' => $results['failed'],
                    'skipped' => $results['skipped'] ?? 0
                ]);

                $this->newLine();

            } catch (\Exception $e) {
                $this->error("  ✗ Error processing {$connection['customer_name']}: {$e->getMessage()}");
                
                $hasErrors = true;
                $newLogs .= "[" . date('Y-m-d H:i:s') . "] EXCEPCIÓN - {$connection['customer_name']}\n" .
                    "  Mensaje: {$e->getMessage()}\n" .
                    "  Archivo: {$e->getFile()}\n" .
                    "  Línea: {$e->getLine()}\n\n";
                
                Log::error("Error procesando cliente", [
                    'customer' => $connection['customer_name'],
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                $totalFailed++;
                continue;
            }
        }

        $finalContent = $newLogs . ($existingContent ? "\n" . str_repeat("=", 80) . "\n\n" . $existingContent : '');
        file_put_contents($errorLogPath, $finalContent);

        // Resumen final
        $this->newLine();
        $this->info("=== SUMMARY ===");
        $this->info("Total images moved: {$totalSuccess}");
        
        if ($totalFailed > 0) {
            $this->error("Total images failed: {$totalFailed}");
        } else {
            $this->info("Total images failed: {$totalFailed}");
        }
        
        $this->info("Total images skipped: {$totalSkipped}");

        // MENSAJE sobre archivo de log
        $this->newLine();
        if ($hasErrors || $hasSuccess) {
            $this->info("Log guardado en:");
            $this->line("  " . str_replace(base_path(), '', $errorLogPath));
        }

        Log::info('=== FIN MOVIMIENTO DE IMÁGENES ===', [
            'total_moved' => $totalSuccess,
            'total_failed' => $totalFailed,
            'total_skipped' => $totalSkipped,
            'has_errors' => $hasErrors,
            'has_success' => $hasSuccess
        ]);

        return $totalFailed > 0 ? 1 : 0;
    }
}
