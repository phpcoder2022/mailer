<?php

namespace Phpcoder2022\SimpleMailer\Send;

use Phpcoder2022\SimpleMailer\Format\FormatterInterface;
use Phpcoder2022\SimpleMailer\Log\LogFormatter;
use Phpcoder2022\SimpleMailer\Mail\MailerInterface;
use Psr\Log\LogLevel;

/**
 * @psalm-type ErrorMessage = array{fieldName: string, message: string}
 * @psalm-type ListOfErrorMessages = non-empty-list<ErrorMessage>
 * @psalm-type FormatFormDataResult = array{mode: 'mail', message: string} | array{mode: 'error', messages: ListOfErrorMessages}
 */
final class Sender
{
    public function __construct(
        private readonly FormatterInterface $formatter,
        private readonly LogFormatter $logger,
        private readonly MailerInterface $mailer,
        private readonly SendResponseFormatter $sendResponseFormatter,
    ) {
    }

    public function sendForm(array $formData): SendResponseResult
    {
        $formatResult = $this->formatter->format($formData);
        $formComplete = $formatResult['mode'] === 'mail';
        if ($formComplete) {
            /** @psalm-suppress PossiblyUndefinedArrayOffset : psalm сплющил исходный тип, ошибка ложноположительная  */
            $operationResult = $this->mailer->sendMail($formatResult['message']);
            $this->logger->write(
                data: ['formData' => $formData, 'result' => $operationResult],
                level: $operationResult ? LogLevel::INFO : LogLevel::WARNING,
            );
        } else {
            $operationResult = false;
        }
        return $this->sendResponseFormatter->getResult(
            $operationResult,
            $formComplete,
            $formatResult['messages'] ?? null
        );
    }
}
