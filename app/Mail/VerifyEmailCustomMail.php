<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmailCustomMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $verificationUrl;
    public string $userName;

    public function __construct(string $verificationUrl, string $userName = 'usuario')
    {
        $this->verificationUrl = $verificationUrl;
        $this->userName = $userName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verifica tu correo electrónico'
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
        $url = e($this->verificationUrl);
        $name = e($this->userName);

        $logoUrl = 'https://res.cloudinary.com/dswoi9u5q/image/upload/v1776977615/destinariologo3_hkmmou.png';
        $heroImageUrl = 'https://res.cloudinary.com/dswoi9u5q/image/upload/v1776985277/destinariologo1_jwqavg.png';

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verifica tu correo electrónico</title>
</head>
<body style="margin:0; padding:0; background-color:#EDEDED; font-family:Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#EDEDED;">
        <tr>
            <td align="center" style="padding:24px 16px;">

                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px; border-collapse:collapse;">

                    <!-- Bloque naranja + imagen -->
                    <tr>
                        <td align="center" style="background-color:#FF7608; border-top-left-radius:21px; border-top-right-radius:21px; padding:31px 0 0 0;">
                            <img
                                src="{$logoUrl}"
                                alt="Destinario"
                                style="display:block; width:404px; max-width:90%; height:auto; border:0; margin:0 auto 24px auto;"
                            >

                            <img
                                src="{$heroImageUrl}"
                                alt="Bienvenida a Destinario"
                                style="display:block; width:600px; max-width:100%; height:auto; border:0; margin:0 auto;"
                            >
                        </td>
                    </tr>

                    <!-- Bloque blanco -->
                    <tr>
                        <td align="center" style="background-color:#FFFFFF; border-bottom-left-radius:21px; border-bottom-right-radius:21px; padding:40px 64px 48px 64px;">
                            
                            <div style="color:#000000; font-size:36px; line-height:1.15; font-weight:700; text-align:center; margin:0 0 24px 0;">
                                ¡Gracias por unirte a nuestra comunidad!
                            </div>

                            <div style="color:#000000; font-size:16px; line-height:1.5; font-weight:400; text-align:center; margin:0 0 24px 0;">
                                Hola, {$name}.
                                <br><br>
                                Mantente al tanto de las novedades que traemos para la comunidad y sé parte de esta experiencia única e inigualable.
                            </div>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto;">
                                <tr>
                                    <td align="center" bgcolor="#00BF63" style="border-radius:24px;">
                                        <a
                                            href="{$url}"
                                            style="display:inline-block; padding:8px 24px; color:#000000; background-color:#00BF63; border-radius:24px; text-decoration:none; font-size:16px; line-height:24px; font-weight:700;"
                                        >
                                            Ir a la página
                                        </a>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding-top:24px;">
                            <div style="max-width:413px; color:#FF7608; font-size:14px; line-height:1.5; text-align:center;">
                                © 2026, Todos los derechos Reservados. Destinario
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding-top:16px;">
                            <div style="max-width:413px; color:rgba(0,0,0,0.50); font-size:14px; line-height:1.5; text-align:center;">
                                Este es un correo electrónico generado automáticamente.
                                <br>
                                No responda a este mensaje.
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