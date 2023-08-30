<?php

namespace Phpcoder2022\SimpleMailer\Send;

use Phpcoder2022\SimpleMailer\Format\FormatterInterface;
use Phpcoder2022\SimpleMailer\Mail\Mailer;
use Psr\Log\LoggerInterface;
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
        private readonly LoggerInterface $logger,
        private readonly Mailer $mailer,
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
            $this->logger->log(
                $operationResult ? LogLevel::INFO : LogLevel::WARNING,
                '',
                ['formData' => $formData, 'result' => $operationResult],
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
