<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CommandFinishedMail extends Mailable
{
    use Queueable, SerializesModels;


    public string $command;
    public string $output;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $command, string $output)
    {
        //
        $this->command = $command;
        $this->output = $output;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('vendor.emails.command-finished')->subject('Tarea Finalizada ' . $this->command);
    }
}
