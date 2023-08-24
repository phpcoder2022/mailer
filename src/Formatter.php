<?php

namespace Phpcoder2022\SimpleMailer;

/**
 * @psalm-import-type ErrorMessage from Mailer
 * @psalm-import-type FormatFormDataResult from Mailer
 */

final class Formatter
{
    public const FIELD_NAME_TEMPLATE = 'FIELD';
    public const FIELD_NAME_PREG = '/' . self::FIELD_NAME_TEMPLATE . '/u';
    public const NUMBER_TEMPLATE = 'NUM';
    public const NUMBER_PREG = '/' . self::NUMBER_TEMPLATE . '/u';

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

    /** @var array<string, string> */
    private array $formData;

    public function __construct(
        private FieldsData $fieldsData,
        array $formData
    ) {
        $this->formData = [];
        foreach ($formData as $paramKey => $paramValue) {
            if (is_string($paramKey) && is_string($paramValue)) {
                $this->formData[$paramKey] = $paramValue;
            }
        }
    }

    private static function checkRusName(string $value): bool
    {
        return preg_match('/^([А-ЯЁ]{2,}([А-ЯЁ \-]*[А-ЯЁ])?)?$/iu', $value)
            && !preg_match('/\-{2}| {2}|\- \-/u', $value);
    }

    private static function getFieldName(string $fieldKey, int $strNumber, int $intNumber): string
    {
        $rusName = self::FIELDS_DATA[$fieldKey]['name'];
        if ($genderNumeral = self::getGenderNumeral($strNumber, GrammaticalGender::MALE, $fieldKey)) {
            $rusName = $genderNumeral . ' ' . mb_strtolower($rusName);
        }
        if ($intNumber >= 0) {
            $rusName = "$rusName $intNumber";
        }
        return $rusName;
    }

    private static function getGenderNumeral(int $strNumber, GrammaticalGender $grammaticalGender, string $fieldKey): string
    {
        if (!($strNumber >= 0 && $strNumber < count(self::RUS_NUMERALS))) {
            return '';
        }
        $rusNumber = self::RUS_NUMERALS[$strNumber];
        return mb_strtoupper(mb_substr($rusNumber, 0, 1))
            . (
                $fieldKey === 'email'
                    ? mb_substr($rusNumber, 1)
                    : mb_substr($rusNumber, 1, -2) . ($strNumber === 2 ? 'ье' : 'ое')
            );
    }

    /**
     * @return FormatFormDataResult
     */
    public function format(): array
    {
        $copyFormData = $this->formData;
        $intermediateResultArr = [];
        /** @var list<ErrorMessage> $errors */
        $errors = [];
        $index = 0;
        $notExistMessage = 'Обязательное поле «FIELD» не заполнено';
        $maxLengthMessage = 'Поле «FIELD» длиннее NUM символов';
        $templatePreg = '/FIELD/u';
        $numPreg = '/NUM/u';
        foreach (self::FIELDS_DATA as $key => $subArr) {
            $maxlength = array_key_exists('maxlength', $subArr) ? $subArr['maxlength'] : FieldData::DEFAULT_MAX_LENGTH;
            if ((array_key_exists('required', $subArr) || array_key_exists('requiredOnlyOriginal', $subArr))
                && !array_key_exists($key, $copyFormData)
            ) {
                $errors[] = [
                    'fieldName' => $key,
                    'message' => preg_replace(
                        $templatePreg,
                        $subArr['name'],
                        ($subArr['errorMessageAsNotExists'] ?? null) && ($subArr['errorMessage'] ?? null)
                            ? $subArr['errorMessage']
                            : $notExistMessage
                    ),
                ];
            }
            foreach ($copyFormData as $paramKey => $paramValue) {
                if (preg_match(
                    '/^(?<strNumber>' . join('|', self::ENG_NUMERALS) . ')?-?' . $key . '-?(?<intNumber>\d*)$/',
                    $paramKey,
                    $matches
                )) {
                    $strNumber = array_search($matches['strNumber'], self::ENG_NUMERALS);
                    if ($strNumber === false) {
                        $strNumber = -1;
                    }
                    $tempArr = [
                        'index' => $index,
                        'key' => $key,
                        'strNumber' => intval($strNumber),
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
                if ($index && mb_strlen($valueText) > FieldData::DEFAULT_MAX_LENGTH) {
                    $errors[] = [
                        'fieldName' => $key,
                        'message' => preg_replace(
                            [$templatePreg, $numPreg],
                            [$key, FieldData::DEFAULT_MAX_LENGTH],
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
}
