<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordCustomMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $resetUrl;
    public string $userName;

    public function __construct(string $resetUrl, string $userName = 'usuario')
    {
        $this->resetUrl = $resetUrl;
        $this->userName = $userName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Restablece tu contraseña'
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml()
        );
    }

    protected function buildHtml(): string
    {
        $url = e($this->resetUrl);
        $name = e($this->userName);
        $logoUrl = 'https://res.cloudinary.com/dswoi9u5q/image/upload/v1776977615/destinariologo3_hkmmou.png';

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer de contraseña</title>
</head>
<body style="margin:0; padding:0; background-color:#F5F5F5; font-family: Arial, Helvetica, sans-serif;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#F5F5F5; margin:0; padding:0;">
        <tr>
            <td align="center" style="padding:40px 16px;">

                <!-- Contenedor principal -->
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="width:600px; max-width:600px; border-collapse:collapse;">
                    
                    <!-- Encabezado naranja -->
                    <tr>
                        <td align="center" style="background-color:#FF7608; border-top-left-radius:21px; border-top-right-radius:21px; padding:31px 20px;">
                            <img 
                                src="{$logoUrl}" 
                                alt="Destinario"
                                style="display:block; width:404px; max-width:100%; height:auto; border:0;"
                            >
                        </td>
                    </tr>

                    <!-- Caja blanca -->
                    <tr>
                        <td align="center" style="background-color:#FFFFFF; border-bottom-left-radius:21px; border-bottom-right-radius:21px; padding:64px;">
                            
                            <!-- Título -->
                            <div style="color:#000000; font-size:40px; line-height:1.2; font-weight:500; text-align:center; margin:0 0 24px 0;">
                                Restablecimiento de contraseña
                            </div>

                            <!-- Texto -->
                            <div style="color:#000000; font-size:16px; line-height:1.6; font-weight:400; text-align:center; margin:0 0 24px 0;">
                                Hola, {$name}
                                <br><br>
                                Recibimos una solicitud para restablecer tu cuenta de Destinario.
                                <br>
                                Da click en el botón para redirigirte a la recuperación de tu contraseña.
                            </div>

                            <!-- Botón -->
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto;">
                                <tr>
                                    <td align="center" bgcolor="#00BF63" style="border-radius:24px;">
                                        <a 
                                            href="{$url}"
                                            style="
                                                display:inline-block;
                                                width:206px;
                                                padding:8px 24px;
                                                font-size:16px;
                                                line-height:24px;
                                                font-weight:700;
                                                color:#000000;
                                                text-decoration:none;
                                                text-align:center;
                                                border-radius:24px;
                                                background-color:#00BF63;
                                            "
                                        >
                                            Restablecer contraseña
                                        </a>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding-top:24px;">
                            <div style="width:100%; max-width:413px; color:#FF7608; font-size:16px; line-height:1.5; font-weight:400; text-align:center;">
                                © 2026, Todos los derechos Reservados. Destinario
                            </div>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>

HTML;
    }
}