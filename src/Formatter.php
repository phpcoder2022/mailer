<?php

namespace Phpcoder2022\SimpleMailer;

/**
 * @psalm-import-type ErrorMessage from Mailer
 * @psalm-import-type FormatFormDataResult from Mailer
 * @psalm-type TempArr = array{index: int<0, max>, key: string, strNumber: int<-1, max>, intNumber: int<-1, max>, originalParamKey: string, value: string}
 */

final class Formatter
{
    public const FIELD_NAME_TEMPLATE = 'FIELD';
    public const FIELD_NAME_PREG = '/' . self::FIELD_NAME_TEMPLATE . '/u';
    public const NUMBER_TEMPLATE = 'NUM';
    public const NUMBER_PREG = '/' . self::NUMBER_TEMPLATE . '/u';
    private const NOT_EXISTS_MESSAGE = 'Обязательное поле «' . self::FIELD_NAME_TEMPLATE . '» не заполнено';
    private const MAX_LENGTH_MESSAGE = 'Поле «' . self::FIELD_NAME_TEMPLATE . '» '
        . 'длиннее ' . self::NUMBER_TEMPLATE . ' символов';
    private const RUS_NUMERALS = [
        'первый', 'второй', 'третий', 'четвёртый', 'пятый', 'шестой', 'седьмой', 'восьмой', 'девятый', 'десятый'
    ];
    private const ENG_NUMERALS = [
        'first', 'second', 'third', 'fourth', 'fifth', 'sixth', 'seventh', 'eighth', 'ninth', 'tenth'
    ];

    /** @var array<string, string> */
    private array $formData;
    /** @var array<string, string> */
    private array $notProcessedFormData;
    /** @var list<ErrorMessage> $errors */
    private array $errors;

    public function __construct(
        private readonly FieldsData $fieldsData,
        array $formData
    ) {
        $this->formData = [];
        foreach ($formData as $paramKey => $paramValue) {
            if (is_string($paramKey) && is_string($paramValue)) {
                $this->formData[$paramKey] = $paramValue;
            }
        }
    }

    public static function checkRusName(string $value): bool
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
        $this->notProcessedFormData = $this->formData;
        $intermediateResultArr = [];
        $this->errors = [];
        $index = 0;
        foreach ($this->fieldsData as $fieldData) {
            $this->checkRequiredKeyNotFound($fieldData);
            foreach ($this->notProcessedFormData as $paramKey => $paramValue) {
                if (preg_match(
                    '/^(?<strNumber>' . join('|', self::ENG_NUMERALS) . ')?-?' . $fieldData->key . '-?(?<intNumber>\d*)$/',
                    $paramKey,
                    $matches
                )) {
                    $strNumber = array_search($matches['strNumber'], self::ENG_NUMERALS);
                    if ($strNumber === false) {
                        $strNumber = -1;
                    }
                    $tempArr = [
                        'index' => $index,
                        'key' => $fieldData->key,
                        'strNumber' => max(intval($strNumber), -1),
                        'intNumber' => max(strlen($matches['intNumber']) ? intval($matches['intNumber']) : -1, -1),
                        'originalParamKey' => $paramKey,
                        'value' => trim($paramValue),
                    ];
                    $this->checkValueLongerMaxLength($fieldData, $tempArr);
                    $this->checkValueViaValidators($fieldData, $tempArr);
                    if ($fieldData->required
                        && !mb_strlen($tempArr['value'])
                        && (!$fieldData->required->onlyForOriginalKey || $paramKey === $fieldData->key)
                    ) {
                        $this->errors[] = [
                            'fieldName' => $tempArr['originalParamKey'],
                            'message' => preg_replace(
                                self::FIELD_NAME_PREG,
                                self::getFieldName($fieldData, $tempArr['strNumber'], $tempArr['intNumber']),
                                self::NOT_EXISTS_MESSAGE
                            ),
                        ];
                    }
                    if ($fieldData->replacementValue) {
                        $tempArr['value'] = $fieldData->replacementValue;
                    }
                    $intermediateResultArr[] = $tempArr;
                    unset($this->notProcessedFormData[$paramKey]);
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
        ksort($this->notProcessedFormData);
        $resultStr = '<table border="1">';
        foreach ([$intermediateResultArr, $this->notProcessedFormData] as $index => $arr) {
            foreach ($arr as $key => $data) {
                $keyText = !$index
                    ? self::getFieldName($this->fieldsData->getFromKey($data['key']), $data['strNumber'], $data['intNumber'])
                    : $key;
                $valueText = !$index ? $data['value'] : $data;
                if ($index && mb_strlen($valueText) > FieldData::DEFAULT_MAX_LENGTH) {
                    $this->errors[] = [
                        'fieldName' => $key,
                        'message' => preg_replace(
                            [self::FIELD_NAME_PREG, self::NUMBER_PREG],
                            [$key, FieldData::DEFAULT_MAX_LENGTH],
                            self::MAX_LENGTH_MESSAGE
                        ),
                    ];
                }
                if ($this->errors) {
                    continue;
                }
                $resultStr .= '<tr data-id="' . htmlentities(!$index ? $data['originalParamKey'] : $key) . '"><td><b>'
                    . htmlentities($keyText)
                    . '</b></td><td>'
                    . htmlentities($valueText)
                    . '</td></tr>';
            }
        }
        if ($this->errors) {
            /** @var list<ErrorMessage> $processedErrors */
            $processedErrors = [];
            foreach ($this->errors as $error) {
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

    private function checkRequiredKeyNotFound(FieldData $fieldData): void
    {
        if ($fieldData->required && !array_key_exists($fieldData->key, $this->notProcessedFormData)) {
            $this->addError(
                $fieldData->key,
                preg_replace(
                    self::FIELD_NAME_PREG,
                    $fieldData->name,
                    $fieldData->errorMessageAsNotExists && $fieldData->errorMessage
                        ? $fieldData->errorMessage
                        : self::NOT_EXISTS_MESSAGE
                ),
            );
        }
    }

    /**
     * @param FieldData $fieldData
     * @param TempArr $tempArr
     * @return void
     */
    private function checkValueLongerMaxLength(FieldData $fieldData, array $tempArr): void
    {
        if ($fieldData->maxLength !== FieldData::NO_MAX_LENGTH
            && mb_strlen($tempArr['value']) > $fieldData->maxLength
        ) {
            $this->addError(
                $tempArr['originalParamKey'],
                preg_replace(
                    [self::FIELD_NAME_PREG, self::NUMBER_PREG],
                    [
                        self::getFieldName($fieldData, $tempArr['strNumber'], $tempArr['intNumber']),
                        $fieldData->maxLength
                    ],
                    self::MAX_LENGTH_MESSAGE
                ),
            );
        }
    }

    /**
     * @param FieldData $fieldData
     * @param TempArr $tempArr
     * @return void
     */
    private function checkValueViaValidators(FieldData $fieldData, array $tempArr): void
    {
        if ($fieldData->validateRegExp && !preg_match($fieldData->validateRegExp, $tempArr['value'])
            || $fieldData->validateCallback && !($fieldData->validateCallback)($tempArr['value'])
        ) {
            $this->addError(
                $tempArr['originalParamKey'],
                preg_replace(
                    self::FIELD_NAME_PREG,
                    self::getFieldName($fieldData, $tempArr['strNumber'], $tempArr['intNumber']),
                    $fieldData->errorMessage
                ),
            );
        }
    }

    private function addError(string|int|float|null $fieldName, string|int|float|null $message): void
    {
        $this->errors[] = ['fieldName' => strval($fieldName), 'message' => strval($message)];
    }

}
