<?php

namespace App\Console\Commands;

use App\Services\ImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MoveImagesFromSecondToMainDisk extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
        protected $signature = 'command:move-to-main-disk {--connection=} {--project_id=} {--limit=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move images from second to main disk. Frecuently some images are saved in the second disk by mistake or not available the main disk.';

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
        
        $this->info('Start moving images...');

        $conn_field = $this->option('connection');

        if (!$conn_field) {
            $this->error('--connection option is required.');
            return 1;
        }


        $result = DB::select('SELECT project_name FROM api_project_conn WHERE conn_field = ?', [$conn_field]);

        if (empty($result)) {
            $this->error('No project found for the given connection field.');
            return 1;
        }

        $customer_name = $result[0]->project_name;

        $project_id = $this->option('project_id');

        if (!$project_id) {
            $this->error('--project_id option is required.');
            return 1;
        }

        DB::setDefaultConnection($conn_field);

        $limit = $this->option('limit');

        $limit = $limit ? (int)$limit : 0;

        $results = ImageService::moveToMainDiskWhenImagesHaveBeenSavedInSecondDisk($project_id, $customer_name, $limit);

        $this->info("Total images moved: " . $results['success']);

        $this->warn("Total images failed: " . $results['failed']);
    
        return 0;
    }
}
