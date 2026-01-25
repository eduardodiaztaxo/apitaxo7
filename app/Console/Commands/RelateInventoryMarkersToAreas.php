<?php

namespace App\Console\Commands;

use App\Models\Maps\InventoryMarkerLevelArea;
use App\Models\Maps\MapPolygonalArea;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RelateInventoryMarkersToAreas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:relate-inventory-markers-to-areas {--connection=} {--level=} {--areas_ids=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Join markers to areas by levels for inventory';

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
        
        $this->info('Starting relate inventory markers...');

        $conn_field = $this->option('connection');

        if (!$conn_field) {
            $this->error('--connection option is required.');
            return 1;
        }

        $this->level = $this->option('level');
        $this->areas_ids = $this->option('areas_ids');

        if (!$this->level && !$this->areas_ids) {
            $this->error('--level or --areas_ids option is required.');
            return 1;
        }

        DB::setDefaultConnection($conn_field);

        //Level 2 green areas, no limits shared areas
        if ($this->level==2) {
            $queryBuilder = MapPolygonalArea::where('level', '=', $this->level);
        } else {
            $queryBuilder = MapPolygonalArea::whereIn('id', explode(',', $this->areas_ids))->where('level', '=', 2);
        }


        $areas = $queryBuilder->get();

        foreach ($areas as $area) {
            
            InventoryMarkerLevelArea::where('area_id', '=', $area->id)->where('level', '=', $area->level)->delete();

            $markers = $area->inventory_markers_by_coordinates();

            

            $this->info('Area ID: ' . $area->id . ' Name: ' . $area->name . ' - Inventory Markers found: ' . count($markers));

            foreach ($markers as $marker) {

                InventoryMarkerLevelArea::updateOrCreate(
                    [
                        'inventory_id' => $marker->inv_id,
                        'area_id' => $area->id,
                        'level' => $area->level
                    ],
                );
            }
        
        }

        $this->info('Join Inventory Markers to Polygons complete successfully.');
    
        return 0;
    }
}
