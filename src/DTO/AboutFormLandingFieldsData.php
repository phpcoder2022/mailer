<?php

namespace Phpcoder2022\SimpleMailer\DTO;

use Phpcoder2022\SimpleMailer\Format\Formatter;
use Phpcoder2022\SimpleMailer\Format\GrammaticalGender;

class AboutFormLandingFieldsData extends FieldsData
{
    public static function createWithData(): FieldsData
    {
        return new self(
            new FieldData(
                key: 'name',
                name: 'Имя',
                grammaticalGender: GrammaticalGender::MIDDLE,
                maxLength: 20,
                validateCallback: Formatter::class . '::checkRusName',
                required: new RequiredData(true),
                errorMessage: 'Поле «' . Formatter::FIELD_NAME_TEMPLATE . '» содержит некорректное имя',
            ),
            new FieldData(
                key: 'call',
                name: 'Как обращаться',
                grammaticalGender: GrammaticalGender::MIDDLE,
                maxLength: 20,
                validateCallback: Formatter::class . '::checkRusName',
                errorMessage: 'Поле «' . Formatter::FIELD_NAME_TEMPLATE . '» содержит некорректное имя',
            ),
            new FieldData(
                key: 'email',
                name: 'Email',
                grammaticalGender: GrammaticalGender::MALE,
                maxLength: FieldData::NO_MAX_LENGTH,
                validateRegExp: FieldData::VALIDATE_AS_JS_PLUGIN,
                required: new RequiredData(true),
                errorMessage: 'Поле «' . Formatter::FIELD_NAME_TEMPLATE . '» содержит некорректный email',
            ),
            new FieldData(
                key: 'message',
                name: 'Сообщение',
                grammaticalGender: GrammaticalGender::MIDDLE,
                required: new RequiredData(true),
            ),
            new FieldData(
                key: 'agreement',
                name: 'Согласие на обработку персональных данных',
                grammaticalGender: GrammaticalGender::MIDDLE,
                validateRegExp: '/^(on|y|1)$/i',
                required: new RequiredData(true),
                errorMessage: 'Вы не дали согласие на обработку персональных данных',
                errorMessageAsNotExists: true,
                replacementValue: 'Да',
            ),
        );
    }
}
