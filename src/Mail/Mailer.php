<?php

namespace Phpcoder2022\SimpleMailer\Mail;

final class Mailer implements MailerInterface
{
    public function __construct(private readonly MailData $mailData)
    {
    }

    public function sendMail(string $html): bool
    {
        if (self::isLocalhost()) {
            return false;
        }
        return mail(
            $this->mailData->getTo(),
            $this->mailData->getSubject(),
            preg_replace(
                ['/></u', '/\s+/u', '/([^\s;]{50}|;)(\S)/u'],
                [">\r\n<", "\r\n", "\\1<span></span\r\n>\\2"],
                $html
            ) . "\r\n",
            [
                'From' => $this->mailData->getFrom(),
                'Reply-To' => $this->mailData->getReplyTo(),
                'Content-Type' => 'text/html; charset=utf-8',
                'X-Sender' => 'PHP/' . phpversion(),
            ],
        );
    }

    private static function isLocalhost(): bool
    {
        /** @psalm-suppress PossiblyUndefinedArrayOffset */
        return !($_SERVER['SERVER_NAME'] ?? '')
            || !($_SERVER['SERVER_ADDR'] ?? '')
            || $_SERVER['SERVER_NAME'] === 'localhost'
            || $_SERVER['SERVER_ADDR'] === '127.0.0.1';
    }
}
