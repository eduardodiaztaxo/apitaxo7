<?php

namespace App\Mail;

use App\Models\SolicitudAsignacion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;


class AssignResponsibleMail extends Mailable
{
    use Queueable, SerializesModels;


    protected $solicitud;

    protected $paths = [];

    protected $asunto = 'Notificación';
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(SolicitudAsignacion $solicitud, array $paths)
    {
        $this->paths = $paths;
        $this->solicitud = $solicitud;
        $this->asunto = 'Asiignación Responsable Activos - N° Solicitud: ' . $solicitud->n_solicitud;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        foreach ($this->paths as $key => $path) {

            $filename = pathinfo($path, PATHINFO_BASENAME);


            $this->attach(storage_path('app') . $path, [
                'as' => 'attachment-' . $key . '-' . $filename,
                'mime' => 'application/pdf'
            ]);
        }




        return $this->markdown('vendor.emails.assign-responsible', [
            'solicitud' => $this->solicitud
        ])->subject($this->asunto);
    }
}
