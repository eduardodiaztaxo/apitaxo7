<?php

namespace App\Mail;

use App\Models\SolicitudAsignacion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;


class SendBlankDocument extends Mailable
{
    use Queueable, SerializesModels;


    protected $solicitud;

    protected $path = '';

    protected $asunto = 'Notificación';
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(SolicitudAsignacion $solicitud, String $path)
    {
        $this->path = $path;
        $this->solicitud = $solicitud;
        $this->asunto = 'Emisión Acta en Blanco - Folio: ' . $solicitud->n_solicitud;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $pdf_acta = storage_path('app/actas') . $this->path;

        return $this->markdown('vendor.emails.blank-document', [
            'solicitud' => $this->solicitud
        ])
            ->attach($pdf_acta, [
                'as' => 'acta-entrega-' . $this->solicitud->n_solicitud . '.pdf',
                'mime' => 'application/pdf'
            ])->subject($this->asunto);
    }
}
