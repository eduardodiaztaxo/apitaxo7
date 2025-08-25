<?php

namespace App\Console\Commands;

use App\Models\Maps\MapPolygonalArea;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateMinMaxLatLngAreas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:calculate-max-min-lat-lng {--connection=} {--level=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate Max Min Lat Lng Polygon';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    protected $level = 0;


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

        $this->info('Starting update data...');

        $conn_field = $this->option('connection');

        if (!$conn_field) {
            $this->error('--connection option is required.');
            return 1;
        }

        $this->level = $this->option('level');

        if (!$this->level) {
            $this->error('--cycle option is required.');
            return 1;
        }

        DB::setDefaultConnection($conn_field);



        MapPolygonalArea::where(function ($query) {
            $query->orWhereNull('min_lat');
            $query->orWhereNull('max_lat');
            $query->orWhereNull('min_lng');
            $query->orWhereNull('max_lng');
        })->where('level', '=', $this->level)->chunk(100, function ($areas) {
            foreach ($areas as $area) {
                $area->updateMinMaxLatLng();
                $this->info("Updated area ID {$area->id} with min/max lat/lng.");
            }
        });


        $this->info('Update Polygons successfully.');


        return 0;
    }
}
