<?php

namespace App\Support\Mail;

use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

/**
 * Wrapper around PHP Mailer
 */
class Mailer
{
    private PHPMailer $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);

        $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output

        $this->mailer->isSMTP();                                            //Send using SMTP
        $this->mailer->Host       = config('mail.mailers.smtp.host');                     //Set the SMTP server to send through
        $this->mailer->SMTPAuth   = true;                                   //Enable SMTP authentication
        $this->mailer->Username   = config('mail.mailers.smtp.username');                     //SMTP username
        $this->mailer->Password   = config('mail.mailers.smtp.password');                               //SMTP password

        $this->mailer->Port       = config('mail.mailers.smtp.port');                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

        $this->mailer->XMailer = 'QrCodeMailer';

        $enc = config('mail.mailers.smtp.encryption');

        if ($enc && array_search(
            $enc,
            [PHPMailer::ENCRYPTION_SMTPS, PHPMailer::ENCRYPTION_STARTTLS]
        ) !== false) {
            $this->mailer->SMTPSecure = $enc;
        }

        //Recipients
        $this->mailer->setFrom(config('mail.from.address'), config('mail.from.name'));

        if (!config('mail.mailers.smtp.verify_peer')) {
            $this->mailer->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
        }
    }

    public function getMailer()
    {
        return $this->mailer;
    }
}
