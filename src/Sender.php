<?php

namespace Phpcoder2022\SimpleMailer;

/**
 * @psalm-type ErrorMessage = array{fieldName: string, message: string}
 * @psalm-type FormatFormDataResult = array{mode: 'mail', message: string} | array{mode: 'error', messages: non-empty-list<ErrorMessage>}
 */
final class Sender
{
    private const MAIL_MESSAGES = [0 => 'К сожалению, отправить не удалось', 1 => 'Успешно отправлено'];

    private ?bool $lastOperationResult = null;
    private ?bool $lastFormComplete = null;
    private ?string $title = null;
    private ?string $header = null;
    private array $textItems = [];

    public function __construct(
        private readonly FormatterInterface $formatter,
        private readonly LoggerInterface $logger
    ) {
    }

    public function sendForm(array $formData): void
    {
        $formatResult = $this->formatter->format($formData);
        $this->lastFormComplete = $formatResult['mode'] === 'mail';
        $mailMessage = null;
        if ($this->lastFormComplete) {
            /** @psalm-suppress PossiblyUndefinedArrayOffset : psalm сплющил исходный тип, ошибка ложноположительная  */
            $this->lastOperationResult = Mailer::sendMail($formatResult['message']);
            $mailMessage = self::MAIL_MESSAGES[intval($this->lastOperationResult)];
            $this->logger->write(['formData' => $formData, 'result' => $this->lastOperationResult]);
        } else {
            $this->lastOperationResult = false;
        }
        $this->header = $mailMessage ?? 'Форма неправильно заполнена';
        $this->textItems = $formatResult['messages'] ?? [$this->lastOperationResult
            ? ['message' => 'Мы постараемся ответить в ближайшее время']
            : ['message' => 'Но мы всё равно постараемся ответить Вам']
        ];
        $this->title = $this->lastFormComplete && !$this->lastOperationResult ? 'Ошибка отправки' : $this->header;
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
        $this->logger->write(__METHOD__);
        return json_encode(['header' => $this->header, 'textItems' => $this->textItems], JSON_UNESCAPED_UNICODE);
    }

    public function getResultAsHtml(): ?string
    {
        if (is_null($this->header) || is_null($this->title) || is_null($this->lastFormComplete)) {
            return null;
        }
        $this->logger->write(__METHOD__);
        $messageItems = array_map(fn ($subArr) => ['message' => $subArr['message']], $this->textItems);
        return HtmlViewer::loadTemplate($this->title, $this->header, $messageItems, $this->lastFormComplete);
    }
}
