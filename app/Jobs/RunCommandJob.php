<?php

namespace App\Jobs;

use App\Mail\CommandFinishedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

class RunCommandJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    //public $connection = 'mysql_auth';

    protected string $command;
    protected array $arguments;
    protected string $email;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $command, array $arguments = [], string $email = '')
    {
        //
        $this->command = $command;
        $this->arguments = $arguments;
        $this->email = $email;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Run the artisan command
        Artisan::call($this->command, $this->arguments);

        // (Optional) get the output
        $output = Artisan::output();

        // Send notification email when finished
        if (!empty($this->email))
            Mail::to($this->email)->send(new CommandFinishedMail($this->command, $output));
    }
}
