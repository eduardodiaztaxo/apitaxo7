<?php

namespace App\Console\Commands;

use App\Models\Maps\MapPolygonalArea;
use App\Models\Maps\MarkerLevelArea;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RelateMarkersToAreas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:realte-markers-to-aeras {--connection=} {--level=} {--areas_ids=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Join markers to areas by levels';

    protected $level = 0;

    protected $areas_ids = null;

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

        $this->info('Starting relate markers...');

        $conn_field = $this->option('connection');

        if (!$conn_field) {
            $this->error('--connection option is required.');
            return 1;
        }

        $this->level = $this->option('level');
        $this->areas_ids = $this->option('areas_ids');

        if (!$this->level && !$this->areas_ids) {
            $this->error('--cycle option is required.');
            return 1;
        }

        DB::setDefaultConnection($conn_field);

        if ($this->level) {
            $queryBuilder = MapPolygonalArea::where('level', '=', $this->level);
        } else {
            $queryBuilder = MapPolygonalArea::whereIn('id', explode(',', $this->areas_ids));
        }


        $areas = $queryBuilder->get();

        foreach ($areas as $area) {
            $markers = $area->markers();

            $this->info('Area ID: ' . $area->id . ' Name: ' . $area->name . ' - Markers found: ' . count($markers));

            foreach ($markers as $marker) {

                MarkerLevelArea::updateOrCreate(
                    [
                        'marker_id' => $marker->id,
                        'area_id' => $area->id,
                        'level' => $area->level
                    ],
                );
            }
        }

        foreach ($areas as $area) {

            $area->total_markers = $area->markersLastPhoto()->count();
            $area->total_markers_at = now();
            $area->save();

            $punto = $area->punto;

            if ($punto) {
                $punto->q_poligono = $area->total_markers;
                $punto->save();
            }
        }

        $this->info('Join Markers to Polygons complete successfully.');


        return 0;
    }
}
