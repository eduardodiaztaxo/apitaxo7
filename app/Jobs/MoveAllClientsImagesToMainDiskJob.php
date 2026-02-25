<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class MoveAllClientsImagesToMainDiskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 21600; // 6 horas timeout
    public $tries = 1; // Solo 1 intento

    protected $limit;

    /**
     * Create a new job instance.
     */
    public function __construct(int $limit = 1000)
    {
        $this->limit = $limit;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('MoveAllClientsImagesToMainDiskJob: Iniciando migración masiva de imágenes', [
            'limit' => $this->limit,
            'timestamp' => now()->toDateTimeString()
        ]);

        try {
            // Ejecutar el comando (que procesa todos los clientes internamente)
            $exitCode = Artisan::call('command:move-to-main-disk', [
                '--limit' => $this->limit
            ]);

            $output = Artisan::output();

            // Extraer totales del resumen final
            preg_match('/Total images moved: (\d+)/', $output, $successMatch);
            preg_match('/Total images failed: (\d+)/', $output, $failedMatch);
            preg_match('/Total images skipped: (\d+)/', $output, $skippedMatch);

            $totalSuccess = !empty($successMatch) ? (int)$successMatch[1] : 0;
            $totalFailed = !empty($failedMatch) ? (int)$failedMatch[1] : 0;
            $totalSkipped = !empty($skippedMatch) ? (int)$skippedMatch[1] : 0;

            Log::info('MoveAllClientsImagesToMainDiskJob: Proceso completado exitosamente', [
                'exit_code' => $exitCode,
                'total_exitos' => $totalSuccess,
                'total_fallos' => $totalFailed,
                'total_omitidos' => $totalSkipped,
                'duracion' => now()->toDateTimeString()
            ]);

            // ← ESCRIBIR resumen en el log de migración
            $logPath = storage_path('logs/migrations/migration_log_' . date('Y-m-d') . '.txt');
            
            if (!file_exists(dirname($logPath))) {
                mkdir(dirname($logPath), 0755, true);
            }

            $existingContent = '';
            if (file_exists($logPath)) {
                $existingContent = file_get_contents($logPath);
            }

            $summaryLog = "[" . date('Y-m-d H:i:s') . "] RESUMEN GENERAL (EJECUTADO VÍA JOB)\n" .
                "  Total imágenes movidas: {$totalSuccess}\n" .
                "  Total fallos: {$totalFailed}\n" .
                "  Total omitidas: {$totalSkipped}\n" .
                "  Exit code: {$exitCode}\n" .
                "  Límite configurado: {$this->limit}\n\n";

            $finalContent = $summaryLog . ($existingContent ? str_repeat("=", 80) . "\n\n" . $existingContent : '');
            file_put_contents($logPath, $finalContent);

        } catch (\Exception $e) {
            Log::error('MoveAllClientsImagesToMainDiskJob: Error durante la ejecución', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            // ← REGISTRAR error en el log de migración
            $logPath = storage_path('logs/migrations/migration_log_' . date('Y-m-d') . '.txt');
            
            if (!file_exists(dirname($logPath))) {
                mkdir(dirname($logPath), 0755, true);
            }

            $existingContent = '';
            if (file_exists($logPath)) {
                $existingContent = file_get_contents($logPath);
            }

            $errorLog = "[" . date('Y-m-d H:i:s') . "] ERROR EN JOB GENERAL\n" .
                "  Mensaje: {$e->getMessage()}\n" .
                "  Archivo: {$e->getFile()}\n" .
                "  Línea: {$e->getLine()}\n\n";

            $finalContent = $errorLog . ($existingContent ? str_repeat("=", 80) . "\n\n" . $existingContent : '');
            file_put_contents($logPath, $finalContent);

            throw $e; // Re-lanzar para que Laravel maneje el retry
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('MoveAllClientsImagesToMainDiskJob: Job falló completamente', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        //REGISTRAR fallo en el log de migración
        $logPath = storage_path('logs/migrations/migration_log_' . date('Y-m-d') . '.txt');
        
        if (!file_exists(dirname($logPath))) {
            mkdir(dirname($logPath), 0755, true);
        }

        $existingContent = '';
        if (file_exists($logPath)) {
            $existingContent = file_get_contents($logPath);
        }

        $failLog = "[" . date('Y-m-d H:i:s') . "]JOB FALLÓ COMPLETAMENTE\n" .
            "  Mensaje: {$exception->getMessage()}\n" .
            "  Archivo: {$exception->getFile()}\n" .
            "  Línea: {$exception->getLine()}\n\n";

        $finalContent = $failLog . ($existingContent ? str_repeat("=", 80) . "\n\n" . $existingContent : '');
        file_put_contents($logPath, $finalContent);
    }
}