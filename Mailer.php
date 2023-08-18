<?php

/**
 * @psalm-type ErrorMessage = array{fieldName: string, message: string}
 * @psalm-type ResultMessage = array{result: bool, message: string}
 * @psalm-type FormatFormDataResult = array{mode: 'mail', message: string} | array{mode: 'error', messages: non-empty-list<ErrorMessage>}
 */
final class Mailer
{
    private const DEFAULT_MAX_LENGTH = 500;
    private const FIELDS_DATA = [
        'name' => [
            'name' => 'Имя',
            'maxlength' => 20,
            'func' => 'checkRusName',
            'errorMessage' => 'Поле «FIELD» содержит некорректное имя',
            'requiredOnlyOriginal' => true,
        ],
        'call' => [
            'name' => 'Как обращаться',
            'maxlength' => 20,
            'func' => 'checkRusName',
            'errorMessage' => 'Поле «FIELD» содержит некорректное имя',
        ],
        'email' => [
            'name' => 'Email',
            'maxlength' => null,
            'preg' => '/^ # По возможности старался повторить валидацию js-плагина
                [a-z\-+\/*!?=\#$%^&0-9]
                ([a-z\.\-+\/*!?=\#$%^&0-9]*[a-z\-+\/*!?=\#$%^&0-9])?
                @
                [a-z\-0-9]
                ([a-z\-\.0-9]*[a-z\-0-9])?
                \.
                [a-z]{2,}
              $|^$/ix',
            'errorMessage' => 'Поле «FIELD» содержит некорректный email',
            'requiredOnlyOriginal' => true,
        ],
        'message' => ['name' => 'Сообщение', 'requiredOnlyOriginal' => true],
        'agreement' => [
            'name' => 'Согласие на обработку персональных данных',
            'preg' => '/^(on|y|1)$/i',
            'errorMessage' => 'Вы не дали согласие на обработку персональных данных',
            'requiredOnlyOriginal' => true,
            'errorMessageAsNotExists' => true,
            'replacementValue' => 'Да',
        ],
    ];
    private const RUS_NUMERALS = ['первый', 'второй', 'третий', 'четвёртый', 'пятый', 'шестой', 'седьмой', 'восьмой', 'девятый', 'десятый'];
    private const ENG_NUMERALS = ['first', 'second', 'third', 'fourth', 'fifth', 'sixth', 'seventh', 'eighth', 'ninth', 'tenth'];
    private const LOG_FILE = 'forms.log';
    private static array $numerals;

    /**
     * @param array $formData
     * @param bool $json
     * @return array{result: bool, message: string, formComplete: bool}
     */
    public static function sendForm(array $formData, bool $json): array
    {
        $formatResult = self::formatFormData($formData);
        if ($formatResult['mode'] === 'mail') {
            $sendResult = self::sendMail($formatResult['message']);
            $result = $sendResult['result'];
            self::log(compact('formData', 'json', 'result'));
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
            $message = self::loadTemplate($title, $header, $textItems, $formatResult['mode'] === 'mail');
        }
        return ['result' => $result, 'message' => $message, 'formComplete' => $formatResult['mode'] === 'mail'];
    }

    /**
     * @param string $text
     * @return ResultMessage
     */
    private static function sendMail(string $text): array
    {
        $messages = [0 => 'К сожалению, отправить не удалось', 1 => 'Успешно отправлено'];
        if (!($_SERVER['SERVER_NAME'] ?? null)
            || !($_SERVER['SERVER_ADDR'] ?? null)
            || $_SERVER['SERVER_NAME'] === 'localhost'
            || $_SERVER['SERVER_ADDR'] === '127.0.0.1'
        ) {
            $result = false;
        } else {
            $result = mail(
                'w-wave.radio@yandex.ru',
                'Интеграция: новый клиент',
                preg_replace(
                    ['/></u', '/\s+/u', '/([^\s;]{50}|;)(\S)/u'],
                    [">\r\n<", "\r\n", "\\1<span></span\r\n>\\2"],
                    $text
                ) . "\r\n",
                [
                    'From' => 'letter-collector@w-wave.nebosvod-msk.ru',
                    'Reply-To' => 'phpcoder2022@yandex.ru',
                    'Content-Type' => 'text/html; charset=utf-8',
                    'X-Mailer' => 'PHP/' . phpversion(),
                ],
            );
        }
        return ['result' => $result, 'message' => $messages[intval($result)]];
    }

    private static function getLogTitle(string $title = ''): string
    {
        $maxLen = 26;
        $halfTitleLen = intval(mb_strlen($title) / 2);
        return str_repeat('-', $maxLen / 2 - ($halfTitleLen + mb_strlen($title) % 2))
            . $title
            . str_repeat('-', $maxLen / 2 - $halfTitleLen);
    }

    private static function log(mixed $data): void
    {
        $logDesc = fopen(self::LOG_FILE, 'a');
        if (!flock($logDesc, LOCK_EX)) {
            return;
        }
        fwrite(
            $logDesc,
            self::getLogTitle(date('d.m.Y H:i:s')) . PHP_EOL
                . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL
                . self::getLogTitle() . PHP_EOL
                . PHP_EOL
        );
        fclose($logDesc);
    }

    private static function checkRusName(string $value): bool
    {
        return preg_match('/^([А-ЯЁ]{2,}([А-ЯЁ \-]*[А-ЯЁ])?)?$/iu', $value)
            && !preg_match('/\-{2}| {2}|\- \-/u', $value);
    }

    private static function arraySearch(string $needle, array $haystack): int|string
    {
        $result = array_search($needle, $haystack, true);
        return $result === false ? -1 : $result;
    }

    private static function getFieldName(string $fieldKey, int $strNumber, int $intNumber): string
    {
        self::$numerals ??= array_combine(self::ENG_NUMERALS, self::RUS_NUMERALS);
        $rusName = self::FIELDS_DATA[$fieldKey]['name'];
        if ($strNumber >= 0) {
            $rusNumber = self::RUS_NUMERALS[$strNumber];
            $rusName = mb_strtoupper(mb_substr($rusNumber, 0, 1))
                . (
                    $fieldKey === 'email'
                    ? mb_substr($rusNumber, 1)
                    : mb_substr($rusNumber, 1, -2) . ($strNumber === 2 ? 'ье' : 'ое')
                )
                . ' ' . mb_strtolower($rusName);
        }
        if ($intNumber >= 0) {
            $rusName = "$rusName $intNumber";
        }
        return $rusName;
    }

    /**
     * @param array $formData
     * @return FormatFormDataResult
     */
    private static function formatFormData(array $formData): array
    {
        $copyFormData = $formData;
        $intermediateResultArr = [];
        /** @var list<ErrorMessage> $errors */
        $errors = [];
        $index = 0;
        $notExistMessage = 'Обязательное поле «FIELD» не заполнено';
        $maxLengthMessage = 'Поле «FIELD» длиннее NUM символов';
        $templatePreg = '/FIELD/u';
        $numPreg = '/NUM/u';
        foreach (self::FIELDS_DATA as $key => $subArr) {
            $maxlength = array_key_exists('maxlength', $subArr) ? $subArr['maxlength'] : self::DEFAULT_MAX_LENGTH;
            if ((array_key_exists('required', $subArr) || array_key_exists('requiredOnlyOriginal', $subArr))
                && !array_key_exists($key, $copyFormData)
            ) {
                $errors[] = [
                    'fieldName' => $key,
                    'message' => preg_replace(
                        $templatePreg,
                        $subArr['name'],
                        ($subArr['errorMessageAsNotExists'] ?? null) ? $subArr['errorMessage'] : $notExistMessage
                    ),
                ];
            }
            foreach ($copyFormData as $paramKey => $paramValue) {
                if (preg_match(
                    '/^(?<strNumber>' . join('|', self::ENG_NUMERALS) . ')?-?' . $key . '-?(?<intNumber>\d*)$/',
                    $paramKey,
                    $matches
                )) {
                    $tempArr = [
                        'index' => $index,
                        'key' => $key,
                        'strNumber' => self::arraySearch($matches['strNumber'], self::ENG_NUMERALS),
                        'intNumber' => strlen($matches['intNumber']) ? intval($matches['intNumber']) : -1,
                        'originalParamKey' => $paramKey,
                        'value' => trim($paramValue),
                    ];
                    if ($maxlength && mb_strlen($tempArr['value']) > $maxlength) {
                        $errors[] = [
                            'fieldName' => $tempArr['originalParamKey'],
                            'message' => preg_replace(
                                [$templatePreg, $numPreg],
                                [self::getFieldName($tempArr['key'], $tempArr['strNumber'], $tempArr['intNumber']), $maxlength],
                                $maxLengthMessage
                            ),
                        ];
                    }
                    if (array_key_exists('preg', $subArr) && !preg_match($subArr['preg'], $tempArr['value'])
                        || array_key_exists('func', $subArr)
                        && method_exists(self::class, $subArr['func'])
                        && !($func = self::class . "::$subArr[func]")($tempArr['value'])
                    ) {
                        $errors[] = [
                            'fieldName' => $tempArr['originalParamKey'],
                            'message' => preg_replace(
                                $templatePreg,
                                self::getFieldName($tempArr['key'], $tempArr['strNumber'], $tempArr['intNumber']),
                                $subArr['errorMessage']
                            ),
                        ];
                    }
                    if (
                        (
                            array_key_exists('requiredOnlyOriginal', $subArr)
                                || array_key_exists('required', $subArr)
                        )
                        && !mb_strlen($tempArr['value'])
                        && (array_key_exists('required', $subArr) || $paramKey === $key)
                    ) {
                        $errors[] = [
                            'fieldName' => $tempArr['originalParamKey'],
                            'message' => preg_replace(
                                $templatePreg,
                                self::getFieldName($tempArr['key'], $tempArr['strNumber'], $tempArr['intNumber']),
                                $notExistMessage
                            ),
                        ];
                    }
                    if ($subArr['replacementValue'] ?? null) {
                        $tempArr['value'] = $subArr['replacementValue'];
                    }
                    $intermediateResultArr[] = $tempArr;
                    unset($copyFormData[$paramKey]);
                }
            }
            $index++;
        }
        usort($intermediateResultArr, function (array $a, array $b): int {
            if ($a['index'] !== $b['index']) {
                return $a['index'] - $b['index'];
            }
            if ($a['strNumber'] !== $b['strNumber']) {
                return $a['strNumber'] - $b['strNumber'];
            }
            return $a['intNumber'] - $b['intNumber'];
        });
        ksort($copyFormData);
        $resultStr = '<table border="1">';
        foreach ([$intermediateResultArr, $copyFormData] as $index => $arr) {
            foreach ($arr as $key => $data) {
                $keyText = !$index
                    ? self::getFieldName($data['key'], $data['strNumber'], $data['intNumber'])
                    : $key;
                $valueText = !$index ? $data['value'] : $data;
                if ($index && mb_strlen($valueText) > self::DEFAULT_MAX_LENGTH) {
                    $errors[] = [
                        'fieldName' => $key,
                        'message' => preg_replace(
                            [$templatePreg, $numPreg],
                            [$key, self::DEFAULT_MAX_LENGTH],
                            $maxLengthMessage
                        ),
                    ];
                }
                if ($errors) {
                    continue;
                }
                $resultStr .= '<tr data-id="' . htmlentities(!$index ? $data['originalParamKey'] : $key) . '"><td><b>'
                    . htmlentities($keyText)
                    . '</b></td><td>'
                    . htmlentities($valueText)
                    . '</td></tr>';
            }
        }
        if ($errors) {
            /** @var list<ErrorMessage> $processedErrors */
            $processedErrors = [];
            foreach ($errors as $error) {
                $processedErrors[] = [
                    'fieldName' => strval($error['fieldName']),
                    'message' => strval($error['message']),
                ];
            }
            return ['mode' => 'error', 'messages' => $processedErrors];
        }
        $resultStr .= '</table>';
        return ['mode' => 'mail', 'message' => $resultStr];
    }

    /**
     * @param string $title
     * @param string $header
     * @param array{message: string}[] $textItems
     * @param bool $successFormSent
     * @return string
     */
    private static function loadTemplate(string $title, string $header, array $textItems, bool $successFormSent): string
    {
        $replaced = preg_replace(
            [
                '/#TITLE#/u',
                '/#HEADER#/u',
                '/(<!--\s*)?##SUCCESS_FORM_SEN[TD](\s*-->)?(.*?)(<!--\s*)?##(\s*-->)?/us',
                '/#HTTP_REFER{1,2}ER#/u',
            ],
            [$title, $header, $successFormSent ? '\\3' : '', @$_SERVER['HTTP_REFERER'] ?: '/'],
            file_get_contents('./info.html')
        );
        $dom = new DOMDocument();
        $dom->encoding = 'utf-8';
        @$dom->loadHtml($replaced);
        $textItemsList = $dom->getElementById('text-items');
        $templateItem = $textItemsList->firstElementChild;
        $textItemsList->textContent = '';
        foreach ($textItems as $textItem) {
            $newItem = $templateItem->cloneNode();
            $newItem->textContent = $textItem['message'];
            $textItemsList->appendChild($newItem);
        }
        return '<!doctype html>' . PHP_EOL
            . preg_replace('/<\/(meta|link|br|hr|input)>/ui', '', $dom->documentElement->C14N());
    }
}
