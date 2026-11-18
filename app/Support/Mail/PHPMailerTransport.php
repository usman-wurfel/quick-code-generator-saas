<?php

namespace App\Support\Mail;

use Exception;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\PHPMailer;

use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;

class PHPMailerTransport extends AbstractTransport
{
    private PHPMailer $mailer;

    protected function doSend(SentMessage $message): void
    {
        $this->mailer = (new Mailer)->getMailer();

        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $this->mailer->Subject = $email->getSubject();

        $this->mailer->CharSet = 'UTF-8';

        foreach ($email->getTo() as $recipient) {
            $this->mailer->addAddress($recipient->getAddress(), $recipient->getName());
        }

        foreach ($email->getCc() as $cc) {
            $this->mailer->addCC($cc->getAddress(), $cc->getName());
        }

        foreach ($email->getBcc() as $bcc) {
            $this->mailer->addBCC($bcc->getAddress(), $bcc->getName());
        }

        $this->mailer->isHTML(false);

        $this->mailer->Body = $email->getTextBody();

        if ($email->getHtmlBody()) {
            $this->mailer->isHTML(true);
            $this->mailer->Body = $email->getHtmlBody();
        }

        foreach ($email->getAttachments() as $attachment) {
            $headers = $attachment->getPreparedHeaders();

            $filename = $headers->getHeaderParameter('Content-Disposition', 'filename');

            $this->mailer->addStringAttachment($attachment->getBody(), $filename);
        }

        ob_start();

        $emailSent = false;

        try {
            $this->mailer->send();

            $emailSent = true;
        } catch (\Throwable $th) {
            Log::error('Email send failed: ' . $th->getMessage());
        }

        $debug = ob_get_clean();

        $message->appendDebug($debug);

        if (!$emailSent) {
            Log::error('PHPMailer debug logs: ' . $debug);
            throw new Exception("Email send failed check logs for deubg info.");
        }
    }

    public function __toString(): string
    {
        return sprintf('phpmailer');
    }
}
