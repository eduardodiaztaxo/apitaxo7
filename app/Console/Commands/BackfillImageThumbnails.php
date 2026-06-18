<?php

namespace App\Console\Commands;

use App\Models\Inv_imagenes;
use App\Services\ImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillImageThumbnails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:backfill-image-thumbnails {--connection=} {--project_id=} {--scope=} {--limit=} {--customer_name=} {--etiqueta=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill missing image thumbnails for inventory or emplazamiento records.';

    private const CHUNK_SIZE = 100;
    private const PROGRESS_EVERY = 250;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting thumbnail backfill...');

        $connField = $this->option('connection');
        if (!$connField) {
            $this->error('--connection option is required.');
            return 1;
        }

        $projectId = (int) $this->option('project_id');
        if ($projectId <= 0) {
            $this->error('--project_id option is required.');
            return 1;
        }

        $scope = strtolower(trim((string) $this->option('scope')));
        if (!in_array($scope, ['inventory', 'emplazamiento'], true)) {
            $this->error('--scope option is required and must be inventory or emplazamiento.');
            return 1;
        }

        $projectConnField = $this->resolveProjectConnectionField($projectId);

        $dbConnection = $this->resolveDatabaseConnection($connField, $projectConnField);
        if (!$dbConnection) {
            $this->error('No database connection could be resolved for the given connection field.');
            return 1;
        }

        $customerName = $this->resolveCustomerName($connField, $projectConnField);
        if (!$customerName) {
            $this->error('No customer/project name could be resolved for the given connection field.');
            return 1;
        }

        $etiqueta = trim((string) $this->option('etiqueta'));

        DB::setDefaultConnection($dbConnection);
        $this->info('Using database connection: ' . $dbConnection);

        $limit = (int) $this->option('limit');
        if ($limit < 0) {
            $this->error('--limit must be greater than or equal to 0.');
            return 1;
        }
        $stats = [
            'processed' => 0,
            'created' => 0,
            'skipped' => 0,
            'missing' => 0,
            'failed' => 0,
            'missing_samples' => [],
            'aborted' => false,
            'abort_reason' => null,
        ];

        if ($scope === 'inventory') {
            $this->processInventory($projectId, $customerName, $limit, $stats, $etiqueta);
        } else {
            $this->processEmplazamiento($projectId, $customerName, $limit, $stats, $etiqueta);
        }

        if (!empty($stats['aborted'])) {
            $this->error('Thumbnail backfill aborted: ' . $stats['abort_reason']);
            if (!empty($stats['missing_samples'])) {
                $this->line('Missing file samples:');
                foreach ($stats['missing_samples'] as $sample) {
                    $this->line('- ' . $sample);
                }
            }

            return 1;
        }

        $this->info('Thumbnail backfill finished.');
        $this->info('Processed: ' . $stats['processed']);
        $this->info('Created: ' . $stats['created']);
        $this->warn('Skipped: ' . $stats['skipped']);
        $this->warn('Missing files: ' . $stats['missing']);
        $this->warn('Failed: ' . $stats['failed']);

        if (!empty($stats['missing_samples'])) {
            $this->line('Missing file samples:');
            foreach ($stats['missing_samples'] as $sample) {
                $this->line('- ' . $sample);
            }
        }

        return 0;
    }

    private function resolveProjectConnectionField(int $projectId): ?string
    {
        try {
            $projectConnField = DB::table('project_conn')
                ->where('project_id', $projectId)
                ->value('conn_field');
        } catch (\Throwable $e) {
            $this->warn('Could not read project_conn mapping: ' . $e->getMessage());
            return null;
        }

        if (!empty($projectConnField)) {
            return (string) $projectConnField;
        }

        return null;
    }

    private function resolveCustomerName(string $connField, ?string $projectConnField = null): ?string
    {
        $explicit = trim((string) $this->option('customer_name'));
        if ($explicit !== '') {
            return $explicit;
        }

        $candidates = array_values(array_unique(array_filter([
            $projectConnField,
            $connField,
            str_replace('-', '_', $connField),
            str_replace('_', '-', $connField),
        ])));

        foreach ($candidates as $candidate) {
            try {
                $projectName = DB::table('project_conn')
                    ->where('conn_field', $candidate)
                    ->value('project_name');

                if (!empty($projectName)) {
                    return (string) $projectName;
                }
            } catch (\Throwable $e) {
                $this->warn('Could not read project_conn mapping: ' . $e->getMessage());
                break;
            }
        }

        $this->warn('No mapping found in project_conn; falling back to connection name as customer folder. Use --customer_name to override if needed.');

        return $projectConnField ?: $connField;
    }

    private function resolveDatabaseConnection(string $connField, ?string $projectConnField = null): ?string
    {
        $available = array_keys(config('database.connections', []));

        foreach (array_values(array_unique(array_filter([$projectConnField, $connField]))) as $candidate) {
            if (in_array($candidate, $available, true)) {
                return $candidate;
            }
        }

        $normalizedInput = $this->normalizeConnectionName($connField);
        foreach ($available as $candidate) {
            if ($this->normalizeConnectionName($candidate) === $normalizedInput) {
                return $candidate;
            }
        }

        $inputTokens = array_values(array_unique(array_merge(
            $this->extractConnectionTokens($connField),
            $projectConnField ? $this->extractConnectionTokens($projectConnField) : []
        )));

        if (empty($inputTokens)) {
            return null;
        }

        $bestCandidate = null;
        $bestScore = 0;

        foreach ($available as $candidate) {
            $candidateNormalized = $this->normalizeConnectionName($candidate);
            $candidateTokens = $this->extractConnectionTokens($candidate);

            $overlap = count(array_intersect($inputTokens, $candidateTokens));
            if ($overlap === 0) {
                continue;
            }

            $score = $overlap * 10;
            if (str_starts_with($candidateNormalized, 'mysql_')) {
                $score += 1;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestCandidate = $candidate;
            }
        }

        if ($bestCandidate !== null) {
            return $bestCandidate;
        }

        return null;
    }

    private function normalizeConnectionName(string $connectionName): string
    {
        return strtolower(trim(str_replace(['-', ' '], '_', $connectionName)));
    }

    private function extractConnectionTokens(string $connectionName): array
    {
        $normalized = $this->normalizeConnectionName($connectionName);
        $parts = preg_split('/[^a-z0-9]+/i', $normalized) ?: [];

        $ignored = ['mysql', 'mariadb', 'db', 'database', 'cert', 'certificate'];

        return array_values(array_unique(array_filter($parts, function ($part) use ($ignored) {
            return $part !== '' && !in_array($part, $ignored, true);
        })));
    }

    private function processInventory(int $projectId, string $customerName, int $limit, array &$stats, string $etiqueta = ''): void
    {
        $query = Inv_imagenes::query()
            ->select(['idLista', 'id_img', 'etiqueta', 'picture', 'url_imagen', 'url_picture', 'origen', 'id_proyecto', 'q_descargas'])
            ->where('id_proyecto', $projectId)
            ->orderBy('idLista');

        if ($etiqueta !== '') {
            $query->where('etiqueta', $etiqueta);
            $this->line('Filtering inventory by etiqueta: ' . $etiqueta);
        }

        $query->chunkById(self::CHUNK_SIZE, function ($images) use ($customerName, $limit, &$stats) {
                $this->line('Processing inventory batch: ' . count($images) . ' items');

                foreach ($images as $image) {
                    if (!empty($stats['aborted'])) {
                        return false;
                    }

                    if ($limit > 0 && $stats['processed'] >= $limit) {
                        return false;
                    }

                    $stats['processed']++;

                    if (empty($image->picture) || empty($image->id_img) || empty($image->etiqueta)) {
                        $stats['missing']++;
                        continue;
                    }

                    if (ImageService::getInventoryThumbnailUrlByPicture((int) $image->id_img, (string) $image->etiqueta, (string) $image->picture)) {
                        $stats['skipped']++;
                        continue;
                    }

                    $source = ImageService::resolveStoredImagePathFromUrls(
                        $image->url_imagen ?? null,
                        $image->url_picture ?? null,
                        $customerName,
                        (string) $image->picture
                    );
                    if (!$source) {
                        $this->registerMissingFile($stats, (string) $image->picture);
                        continue;
                    }

                    $thumbnail = ImageService::createThumbnailFromStoredImagePath($image, $customerName, $source['absolute_path']);
                    if ($thumbnail) {
                        $stats['created']++;
                        $this->maybeReportProgress($stats);
                        continue;
                    }

                    $stats['failed']++;
                }

                return !($limit > 0 && $stats['processed'] >= $limit) && empty($stats['aborted']);
            }, 'idLista');
    }

    private function processEmplazamiento(int $projectId, string $customerName, int $limit, array &$stats, string $etiqueta = ''): void
    {
        $query = DB::table('crud_activos_pictures')
            ->select(['id_foto', 'id_activo', 'etiqueta', 'picture', 'url_imagen', 'url_picture', 'origen', 'idProyecto'])
            ->where('idProyecto', $projectId)
            ->orderBy('id_foto');

        if ($etiqueta !== '') {
            $query->where('etiqueta', $etiqueta);
            $this->line('Filtering emplazamiento by etiqueta: ' . $etiqueta);
        }

        $query->chunkById(self::CHUNK_SIZE, function ($pictures) use ($customerName, $limit, &$stats) {
                $this->line('Processing emplazamiento batch: ' . count($pictures) . ' items');

                foreach ($pictures as $picture) {
                    if (!empty($stats['aborted'])) {
                        return false;
                    }

                    if ($limit > 0 && $stats['processed'] >= $limit) {
                        return false;
                    }

                    $stats['processed']++;

                    if (empty($picture->picture) || empty($picture->id_foto) || empty($picture->etiqueta)) {
                        $stats['missing']++;
                        continue;
                    }

                    if (ImageService::getCrudThumbnailUrlByPicture((int) $picture->id_foto, (string) $picture->etiqueta, (string) $picture->picture)) {
                        $stats['skipped']++;
                        continue;
                    }

                    $source = ImageService::resolveStoredImagePathFromUrls(
                        $picture->url_imagen ?? null,
                        $picture->url_picture ?? null,
                        $customerName,
                        (string) $picture->picture
                    );
                    if (!$source) {
                        $this->registerMissingFile($stats, (string) $picture->picture);
                        continue;
                    }

                    $thumbnail = ImageService::createCrudThumbnailFromStoredImagePath($picture, $customerName, $source['absolute_path']);
                    if ($thumbnail) {
                        $stats['created']++;
                        $this->maybeReportProgress($stats);
                        continue;
                    }

                    $stats['failed']++;
                }

                return !($limit > 0 && $stats['processed'] >= $limit) && empty($stats['aborted']);
                }, 'id_foto');
    }

    private function registerMissingFile(array &$stats, string $fileName): void
    {
        $stats['missing']++;

        if (count($stats['missing_samples']) < 10) {
            $stats['missing_samples'][] = $fileName;
        }

        $this->maybeReportProgress($stats);
    }

    private function maybeReportProgress(array &$stats): void
    {
        if ($stats['processed'] > 0 && $stats['processed'] % self::PROGRESS_EVERY === 0) {
            $this->line(sprintf(
                'Progress: %d processed | %d created | %d skipped | %d missing | %d failed',
                $stats['processed'],
                $stats['created'],
                $stats['skipped'],
                $stats['missing'],
                $stats['failed']
            ));
        }

        if ($stats['processed'] >= 250 && $stats['created'] === 0 && $stats['missing'] >= (int) floor($stats['processed'] * 0.95)) {
            $stats['aborted'] = true;
            $stats['abort_reason'] = 'no thumbnails created after 250 processed records; the customer folder likely does not match the stored image path. Use --customer_name to override the folder name.';
        }
    }
}