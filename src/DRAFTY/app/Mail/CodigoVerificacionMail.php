<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Correo que prepara el mensaje de codigoverificacion.
 */
class CodigoVerificacionMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function __construct(
        public string $codigo
    ) {
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Código de verificación DRAFTY',
        );
    }

    /**
     * Ejecuta la logica principal de esta parte del proyecto.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.codigo-verificacion',
        );
    }
}
