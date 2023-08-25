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

    private static function getFieldName(FieldData $fieldData, int $strNumber, int $intNumber): string
    {
        $rusName = $fieldData->name;
        if ($genderNumeral = self::getGenderNumeral($strNumber, $fieldData->grammaticalGender)) {
            $rusName = mb_strtoupper(mb_substr($genderNumeral, 0, 1)) . mb_substr($genderNumeral, 1)
                . ' ' . mb_strtolower($rusName);
        }
        if ($intNumber >= 0) {
            $rusName = "$rusName $intNumber";
        }
        return $rusName;
    }

    private static function getGenderNumeral(int $strNumber, GrammaticalGender $grammaticalGender): string
    {
        if (!($strNumber >= 0 && $strNumber < count(self::RUS_NUMERALS))) {
            return '';
        }
        $maleRusNumber = self::RUS_NUMERALS[$strNumber];
        switch ($grammaticalGender) {
            case GrammaticalGender::MALE:
                return $maleRusNumber;
            case GrammaticalGender::FEMALE:
                $tail = 'ая';
                // no break
            default:
                $tail ??= 'ое';
                return mb_substr($maleRusNumber, 0, -2) . ($strNumber === 2 ? 'ь' . mb_substr($tail, 1) : $tail);
        }
    }

    /**
     * @return FormatFormDataResult
     */
    public function format(): array
    {
        $notProcessedFormData = $this->formData;
        $intermediateResultArr = [];
        /** @var list<ErrorMessage> $errors */
        $errors = [];
        $index = 0;
        $notExistMessage = 'Обязательное поле «FIELD» не заполнено';
        $maxLengthMessage = 'Поле «FIELD» длиннее NUM символов';
        $templatePreg = '/FIELD/u';
        $numPreg = '/NUM/u';
        foreach ($this->fieldsData as $key => $fieldData) {
            if ($fieldData->required && !array_key_exists($key, $notProcessedFormData)) {
                $errors[] = [
                    'fieldName' => $key,
                    'message' => preg_replace(
                        $templatePreg,
                        $fieldData->name,
                        $fieldData->errorMessageAsNotExists && $fieldData->errorMessage
                            ? $fieldData->errorMessage
                            : $notExistMessage
                    ),
                ];
            }
            foreach ($notProcessedFormData as $paramKey => $paramValue) {
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
                    if ($fieldData->maxLength !== FieldData::NO_MAX_LENGTH
                        && mb_strlen($tempArr['value']) > $fieldData->maxLength
                    ) {
                        $errors[] = [
                            'fieldName' => $tempArr['originalParamKey'],
                            'message' => preg_replace(
                                [$templatePreg, $numPreg],
                                [self::getFieldName($fieldData, $tempArr['strNumber'], $tempArr['intNumber']), $fieldData->maxLength],
                                $maxLengthMessage
                            ),
                        ];
                    }
                    if ($fieldData->preg && !preg_match($fieldData->preg, $tempArr['value'])
                        || $fieldData->methodName
                            && method_exists(self::class, $fieldData->methodName)
                            && !($func = self::class . "::$fieldData->methodName")($tempArr['value'])
                    ) {
                        $errors[] = [
                            'fieldName' => $tempArr['originalParamKey'],
                            'message' => preg_replace(
                                $templatePreg,
                                self::getFieldName($fieldData, $tempArr['strNumber'], $tempArr['intNumber']),
                                $fieldData->errorMessage
                            ),
                        ];
                    }
                    if ($fieldData->required
                        && !mb_strlen($tempArr['value'])
                        && (!$fieldData->required->onlyForOriginalKey || $paramKey === $key)
                    ) {
                        $errors[] = [
                            'fieldName' => $tempArr['originalParamKey'],
                            'message' => preg_replace(
                                $templatePreg,
                                self::getFieldName($fieldData, $tempArr['strNumber'], $tempArr['intNumber']),
                                $notExistMessage
                            ),
                        ];
                    }
                    if ($fieldData->replacementValue) {
                        $tempArr['value'] = $fieldData->replacementValue;
                    }
                    $intermediateResultArr[] = $tempArr;
                    unset($notProcessedFormData[$paramKey]);
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
        ksort($notProcessedFormData);
        $resultStr = '<table border="1">';
        foreach ([$intermediateResultArr, $notProcessedFormData] as $index => $arr) {
            foreach ($arr as $key => $data) {
                $keyText = !$index
                    ? self::getFieldName($this->fieldsData->getFromKey($data['key']), $data['strNumber'], $data['intNumber'])
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
