<?php

namespace Phpcoder2022\SimpleMailer;

/**
 * @psalm-type ErrorMessage = array{fieldName: string, message: string}
 * @psalm-type ResultMessage = array{result: bool, message: string}
 * @psalm-type FormatFormDataResult = array{mode: 'mail', message: string} | array{mode: 'error', messages: non-empty-list<ErrorMessage>}
 * @psalm-type MailAddressesData = array{To: string, Subject: string, From: string, Reply-To: string}
 */
final class Mailer
{
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
        $formatResult = (new Formatter(AboutFormLandingFieldsData::createWithData(), $formData))->format();
        $formComplete = $formatResult['mode'] === 'mail';
        $sendResult = [];
        if ($formComplete) {
            /** @psalm-suppress PossiblyUndefinedArrayOffset : psalm сплющил исходный тип, ошибка ложноположительная  */
            $sendResult = self::sendMail($formatResult['message']);
            $result = $sendResult['result'];
            $logger->write(compact('formData', 'json', 'result'));
        } else {
            $result = false;
        }
        $header = $sendResult['message'] ?? 'Форма неправильно заполнена';
        $textItems = $formatResult['messages'] ?? [$result
            ? ['message' => 'Мы постараемся ответить в ближайшее время']
            : ['message' => 'Но мы всё равно постараемся ответить Вам']
        ];
        $title = ($sendResult['result'] ?? null) === false ? 'Ошибка отправки' : $header;
        if ($json) {
            $message = json_encode(compact('header', 'textItems'), JSON_UNESCAPED_UNICODE);
        } else {
            $messageItems = array_map(fn ($subArr) => ['message' => $subArr['message']], $textItems);
            $message = HtmlViewer::loadTemplate($title, $header, $messageItems, $formComplete);
        }
        return compact('result', 'message', 'formComplete');
    }

    /**
     * @param string $text
     * @return ResultMessage
     */
    private static function sendMail(string $text): array
    {
        $messages = [0 => 'К сожалению, отправить не удалось', 1 => 'Успешно отправлено'];
        if (self::isLocalhost()) {
            $result = false;
        } else {
            $mailAddressesData = self::getMailAddressesAndSubject();
            $result = mail(
                $mailAddressesData['To'],
                $mailAddressesData['Subject'],
                preg_replace(
                    ['/></u', '/\s+/u', '/([^\s;]{50}|;)(\S)/u'],
                    [">\r\n<", "\r\n", "\\1<span></span\r\n>\\2"],
                    $text
                ) . "\r\n",
                [
                    'From' => $mailAddressesData['From'],
                    'Reply-To' => $mailAddressesData['Reply-To'],
                    'Content-Type' => 'text/html; charset=utf-8',
                    'X-Mailer' => 'PHP/' . phpversion(),
                ],
            );
        }
        return ['result' => $result, 'message' => $messages[intval($result)]];
    }

    private static function isLocalhost(): bool
    {
        /** @psalm-suppress PossiblyUndefinedArrayOffset */
        return !($_SERVER['SERVER_NAME'] ?? '')
            || !($_SERVER['SERVER_ADDR'] ?? '')
            || $_SERVER['SERVER_NAME'] === 'localhost'
            || $_SERVER['SERVER_ADDR'] === '127.0.0.1';
    }

    /**
     * @return MailAddressesData
     */
    private static function getMailAddressesAndSubject(): array
    {
        $addressesFileName = 'mails.txt';
        $lineArr = is_file($addressesFileName)
            ? file($addressesFileName, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES)
            : [];
        $resultArr = ['To' => '', 'Subject' => '', 'From' => '', 'Reply-To' => ''];
        foreach ($resultArr as $entryKey => $_) {
            for ($i = 0; $i < count($lineArr); $i++) {
                if (preg_match('/^\s*#\s*' . $entryKey . ':\s*(.*?)\s*/iu', $lineArr[$i])) {
                    $resultArr[$entryKey] = trim($lineArr[$i + 1] ?? '');
                    break;
                }
            }
            if (!$resultArr[$entryKey]) {
                throw new \UnexpectedValueException("Ключ $entryKey не определён в файле $addressesFileName");
            }
        }
        return $resultArr;
    }
}
