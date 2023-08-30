<?php

namespace Phpcoder2022\SimpleMailer;

/**
 * @psalm-type ErrorMessage = array{fieldName: string, message: string}
 * @psalm-type FormatFormDataResult = array{mode: 'mail', message: string} | array{mode: 'error', messages: non-empty-list<ErrorMessage>}
 * @psalm-type TextsArr = array{mailSuccessHeader: string, mailFailHeader: string, formCompleteFailHeader: string, mailFailTitle: string, mailSuccessTextItem: string, mailFailTextItem: string}
 */
final class Sender
{
    public const DEFAULT_TEXTS_FILE_PATH = 'textsForSendForm.ini';

    /** @var TextsArr */
    private const TEXTS_ARR_STUB = [
        'mailSuccessHeader' => '',
        'mailFailHeader' => '',
        'formCompleteFailHeader' => '',
        'mailFailTitle' => '',
        'mailSuccessTextItem' => '',
        'mailFailTextItem' => '',
    ];

    private ?bool $lastOperationResult = null;
    private ?bool $lastFormComplete = null;
    private ?string $title = null;
    private ?string $header = null;
    private array $textItems = [];

    public function __construct(
        private readonly FormatterInterface $formatter,
        private readonly LoggerInterface $logger,
        private readonly HtmlViewer $htmlViewer,
        private readonly string $textsFilePath = self::DEFAULT_TEXTS_FILE_PATH,
    ) {
    }

    public function sendForm(array $formData): void
    {
        $formatResult = $this->formatter->format($formData);
        $this->lastFormComplete = $formatResult['mode'] === 'mail';
        $texts = $this->getTextsFromFile();
        $mailMessage = null;
        if ($this->lastFormComplete) {
            /** @psalm-suppress PossiblyUndefinedArrayOffset : psalm сплющил исходный тип, ошибка ложноположительная  */
            $this->lastOperationResult = Mailer::sendMail($formatResult['message']);
            $mailMessage = $texts['mail' . ($this->lastOperationResult ? 'Success' : 'Fail') . 'Header'];
            $this->logger->write(['formData' => $formData, 'result' => $this->lastOperationResult]);
        } else {
            $this->lastOperationResult = false;
        }
        $this->header = $mailMessage ?? $texts['formCompleteFailHeader'];
        $this->textItems = $formatResult['messages'] ?? [$this->lastOperationResult
            ? ['message' => $texts['mailSuccessTextItem']]
            : ['message' => $texts['mailFailTextItem']]
        ];
        $this->title = $this->lastFormComplete && !$this->lastOperationResult
            ? $texts['mailFailTitle']
            : $this->header;
    }

    /** @return TextsArr */
    private function getTextsFromFile(): array
    {
        if (!is_file($this->textsFilePath)) {
            $errorMessage = 'Файл настроек \"' . $this->textsFilePath .  '\" для метода ' . __METHOD__ . 'не найден';
            throw new \UnexpectedValueException($errorMessage);
        }
        $texts = parse_ini_file($this->textsFilePath);
        $result = self::TEXTS_ARR_STUB;
        $notExistsKeys = [];
        foreach ($result as $key => $_) {
            $result[$key] = is_string($texts[$key]) ? $texts[$key] : '';
            if (!$result[$key]) {
                $notExistsKeys[] = $key;
            }
        }
        if ($notExistsKeys) {
            $errorMessage = "Тексты в файле \"$this->textsFilePath\": не хватает следующих ключей: "
                . join(', ', $notExistsKeys);
            throw new \UnexpectedValueException($errorMessage);
        }
        return $result;
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
        return $this->htmlViewer->loadTemplate($this->title, $this->header, $messageItems, $this->lastFormComplete);
    }
}
