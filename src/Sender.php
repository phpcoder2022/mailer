<?php

namespace Phpcoder2022\SimpleMailer;

/**
 * @psalm-type ErrorMessage = array{fieldName: string, message: string}
 * @psalm-type FormatFormDataResult = array{mode: 'mail', message: string} | array{mode: 'error', messages: non-empty-list<ErrorMessage>}
 */
final class Sender
{
    private const MAIL_MESSAGES = [0 => 'К сожалению, отправить не удалось', 1 => 'Успешно отправлено'];

    final private function __construct()
    {
    }

    /**
     * @param array $formData
     * @param bool $json
     * @return array{result: bool, message: string, formComplete: bool}
     */
    public static function sendForm(array $formData, bool $json): array
    {
        $logger = new Logger();
        $formatResult = (new Formatter(AboutFormLandingFieldsData::createWithData()))->format($formData);
        $formComplete = $formatResult['mode'] === 'mail';
        $mailMessage = null;
        if ($formComplete) {
            /** @psalm-suppress PossiblyUndefinedArrayOffset : psalm сплющил исходный тип, ошибка ложноположительная  */
            $result = Mailer::sendMail($formatResult['message']);
            $mailMessage = self::MAIL_MESSAGES[intval($result)];
            $logger->write(compact('formData', 'json', 'result'));
        } else {
            $result = false;
        }
        $header = $mailMessage ?? 'Форма неправильно заполнена';
        $textItems = $formatResult['messages'] ?? [$result
            ? ['message' => 'Мы постараемся ответить в ближайшее время']
            : ['message' => 'Но мы всё равно постараемся ответить Вам']
        ];
        $title = $formComplete && !$result ? 'Ошибка отправки' : $header;
        if ($json) {
            $message = json_encode(compact('header', 'textItems'), JSON_UNESCAPED_UNICODE);
        } else {
            $messageItems = array_map(fn ($subArr) => ['message' => $subArr['message']], $textItems);
            $message = HtmlViewer::loadTemplate($title, $header, $messageItems, $formComplete);
        }
        return compact('result', 'message', 'formComplete');
    }
}
