<?php

namespace Phpcoder2022\SimpleMailer\FieldsData;

use Phpcoder2022\SimpleMailer\Format\Formatter;
use Phpcoder2022\SimpleMailer\Format\GrammaticalGender;

final class FieldDataFactory
{
    public const FIELD_TYPE_NAME = 'name';
    public const FIELD_TYPE_CALL = 'call';
    public const FIELD_TYPE_EMAIL = 'email';
    public const FIELD_TYPE_MESSAGE = 'message';
    public const FIELD_TYPE_AGREEMENT = 'agreement';
    public const ERROR_MESSAGE_START_PART = 'Поле «' . Formatter::FIELD_NAME_TEMPLATE . '» содержит некорректн';

    final private function __construct()
    {
    }

    public static function build(string $fieldType, ?RequiredData $required = null): FieldData
    {
        switch ($fieldType) {
            case self::FIELD_TYPE_NAME:
                $name = 'Имя';
                // no break
            case self::FIELD_TYPE_CALL:
                return new FieldData(
                    key: $fieldType,
                    name: $name ?? 'Как обращаться',
                    grammaticalGender: GrammaticalGender::MIDDLE,
                    maxLength: 20,
                    validateCallback: Formatter::class . '::checkRusName',
                    required: $required,
                    errorMessage: self::ERROR_MESSAGE_START_PART . 'ое имя',
                );
            case self::FIELD_TYPE_EMAIL:
                return new FieldData(
                    key: $fieldType,
                    name: 'Email',
                    grammaticalGender: GrammaticalGender::MALE,
                    maxLength: FieldData::NO_MAX_LENGTH,
                    validateRegExp: FieldData::VALIDATE_AS_JS_PLUGIN,
                    required: $required,
                    errorMessage: self::ERROR_MESSAGE_START_PART . 'ый email',
                );
            case self::FIELD_TYPE_MESSAGE:
                return new FieldData(
                    key: $fieldType,
                    name: 'Сообщение',
                    grammaticalGender: GrammaticalGender::MIDDLE,
                    required: $required,
                );
            case self::FIELD_TYPE_AGREEMENT:
                return new FieldData(
                    key: $fieldType,
                    name: 'Согласие на обработку персональных данных',
                    grammaticalGender: GrammaticalGender::MIDDLE,
                    validateRegExp: '/^(on|y|1)$/i',
                    required: $required,
                    errorMessage: 'Вы не дали согласие на обработку персональных данных',
                    errorMessageAsNotExists: true,
                    replacementValue: 'Да',
                );
            default:
                throw new \UnexpectedValueException("Тип $fieldType отсутствует в классе " . self::class);
        }
    }
}
