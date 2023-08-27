<?php

namespace Phpcoder2022\SimpleMailer;

/**
 * @psalm-type ErrorMessage = array{fieldName: string, message: string}
 * @psalm-type FormatFormDataResult = array{mode: 'mail', message: string} | array{mode: 'error', messages: non-empty-list<ErrorMessage>}
 */
final class Sender
{
    private const MAIL_MESSAGES = [0 => 'К сожалению, отправить не удалось', 1 => 'Успешно отправлено'];

    private readonly Formatter $formatter;
    private readonly Logger $logger;
    private bool $isRan = false;
    private bool $lastOperationResult = false;
    private bool $lastFormComplete = false;
    private string $title = '';
    private string $header = '';
    private array $textItems = [];

    public function __construct(FieldsData $fieldsData)
    {
        $this->formatter = new Formatter($fieldsData);
        $this->logger = new Logger();
    }

    public function sendForm(array $formData): void
    {
        $this->isRan = true;
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

    public function getLastOperationResult(): bool
    {
        $this->throwIfNotRan();
        return $this->lastOperationResult;
    }

    public function getLastFormComplete(): bool
    {
        $this->throwIfNotRan();
        return $this->lastFormComplete;
    }

    public function getResultAsJson(): string
    {
        $this->throwIfNotRan();
        return json_encode(['header' => $this->header, 'textItems' => $this->textItems], JSON_UNESCAPED_UNICODE);
    }

    public function getResultAsHtml(): string
    {
        $this->throwIfNotRan();
        $messageItems = array_map(fn ($subArr) => ['message' => $subArr['message']], $this->textItems);
        return HtmlViewer::loadTemplate($this->title, $this->header, $messageItems, $this->lastFormComplete);
    }

    private function throwIfNotRan(): void
    {
        $class = self::class;
        if (!$this->isRan) {
            throw new \LogicException(
                "Отправка формы ни разу не запускалась с этого объекта $class. Результат не существует."
            );
        }
    }
}
