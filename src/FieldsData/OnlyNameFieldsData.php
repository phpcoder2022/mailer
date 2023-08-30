<?php

namespace Phpcoder2022\SimpleMailer\FieldsData;

use Phpcoder2022\SimpleMailer\Format\Formatter;
use Phpcoder2022\SimpleMailer\Format\GrammaticalGender;

class OnlyNameFieldsData extends FieldsData
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
        );
    }
}
