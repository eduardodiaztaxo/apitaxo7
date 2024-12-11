<?php

namespace App\Console\Commands;

use App\Jobs\UpdateImagesAssets as JobsUpdateImagesAssets;
use Illuminate\Console\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class UpdateImagesAssets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:update-images-assets {--username=} {--limit=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Images Assets';

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
        JobsUpdateImagesAssets::dispatch($this->option('username'), $this->option('limit'));

        $mensaje = "done successfully: DB user " . $this->option('username') . " and " . $this->option('limit') . " rows";

        $style = new OutputFormatterStyle('white', 'green', array('bold', 'blink'));
        $this->output->getFormatter()->setStyle('success', $style);

        $this->output->writeln('<success>' . $mensaje . '</success>');
        return true;
    }
}
