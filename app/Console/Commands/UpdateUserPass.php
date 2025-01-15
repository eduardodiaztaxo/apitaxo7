<?php

namespace App\Console\Commands;

use App\Jobs\UpdateUserPassJob;
use Illuminate\Console\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class UpdateUserPass extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:update-user-pass {--username=} {--password=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update User Password';

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
        UpdateUserPassJob::dispatch($this->option('username'), $this->option('password'));

        $mensaje = "done successfully: DB user " . $this->option('username');

        $style = new OutputFormatterStyle('white', 'green', array('bold', 'blink'));
        $this->output->getFormatter()->setStyle('success', $style);

        $this->output->writeln('<success>' . $mensaje . '</success>');
        return true;
    }
}
