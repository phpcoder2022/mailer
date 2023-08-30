<?php

namespace Phpcoder2022\SimpleMailer\Mail;

interface MailerInterface
{
    public function sendMail(string $html): bool;
}
