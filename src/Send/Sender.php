<?php

namespace Phpcoder2022\SimpleMailer\Send;

use Phpcoder2022\SimpleMailer\Format\FormatterInterface;
use Phpcoder2022\SimpleMailer\Mail\Mailer;
use Phpcoder2022\SimpleMailer\ProcessHtml\HtmlViewer;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * @psalm-type ErrorMessage = array{fieldName: string, message: string}
 * @psalm-type FormatFormDataResult = array{mode: 'mail', message: string} | array{mode: 'error', messages: non-empty-list<ErrorMessage>}
 */
final class Sender
{
    private ?bool $lastOperationResult = null;
    private ?bool $lastFormComplete = null;
    private ?string $title = null;
    private ?string $header = null;
    private array $textItems = [];

    public function __construct(
        private readonly FormatterInterface $formatter,
        private readonly LoggerInterface $logger,
        private readonly HtmlViewer $htmlViewer,
        private readonly Mailer $mailer,
        private readonly SendTexts $sendData,
    ) {
    }

    public function sendForm(array $formData): void
    {
        $formatResult = $this->formatter->format($formData);
        $this->lastFormComplete = $formatResult['mode'] === 'mail';
        $mailMessage = null;
        if ($this->lastFormComplete) {
            /** @psalm-suppress PossiblyUndefinedArrayOffset : psalm сплющил исходный тип, ошибка ложноположительная  */
            $this->lastOperationResult = $this->mailer->sendMail($formatResult['message']);
            $mailMessage = $this->sendData->{'mail' . ($this->lastOperationResult ? 'Success' : 'Fail') . 'Header'};
            $this->logger->log(
                $this->lastOperationResult ? LogLevel::INFO : LogLevel::WARNING,
                '',
                ['formData' => $formData, 'result' => $this->lastOperationResult],
            );
        } else {
            $this->lastOperationResult = false;
        }
        $this->header = $mailMessage ?? $this->sendData->formCompleteFailHeader;
        $this->textItems = $formatResult['messages'] ?? [$this->lastOperationResult
            ? ['message' => $this->sendData->mailSuccessTextItem]
            : ['message' => $this->sendData->mailFailTextItem]
        ];
        $this->title = $this->lastFormComplete && !$this->lastOperationResult
            ? $this->sendData->mailFailTitle
            : $this->header;
    }

    public function getLastOperationResult(): ?bool
    {
        return $this->lastOperationResult;
    }

    public function getLastFormComplete(): ?bool
    {
        return $this->lastFormComplete;
    }

    public function getResultAsJson(): ?string
    {
        if (is_null($this->header)) {
            return null;
        }
        $this->logger->info(__METHOD__);
        return json_encode(['header' => $this->header, 'textItems' => $this->textItems], JSON_UNESCAPED_UNICODE);
    }

    public function getResultAsHtml(): ?string
    {
        if (is_null($this->header) || is_null($this->title) || is_null($this->lastFormComplete)) {
            return null;
        }
        $this->logger->info(__METHOD__);
        $messageItems = array_map(fn ($subArr) => ['message' => $subArr['message']], $this->textItems);
        return $this->htmlViewer->loadTemplate($this->title, $this->header, $messageItems, $this->lastFormComplete);
    }
}
