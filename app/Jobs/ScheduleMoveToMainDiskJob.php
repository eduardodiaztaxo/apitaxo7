<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ScheduleMoveToMainDiskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $limit = 5000; // límite por ejecución
        

        $projects = DB::table('api_project_conn')
            ->select('project_id', 'conn_field')
            ->where('status_project', 1)
            ->get();

        $i = 0;

        foreach ($projects as $project) {

            $i++;            

            MoveToMainDiskJob::dispatch(
                $project->conn_field,
                $project->project_id,
                $limit
            )->delay(
                now()->addMinutes($i * 10)
            );
            
        }
    }
}
