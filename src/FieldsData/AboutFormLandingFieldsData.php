<?php

namespace Phpcoder2022\SimpleMailer\FieldsData;

class AboutFormLandingFieldsData extends FieldsData
{
    public static function createWithData(): FieldsData
    {
        return new self(
            FieldDataFactory::build(FieldDataFactory::FIELD_TYPE_NAME, new RequiredData(true)),
            FieldDataFactory::build(FieldDataFactory::FIELD_TYPE_CALL),
            FieldDataFactory::build(FieldDataFactory::FIELD_TYPE_EMAIL, new RequiredData(true)),
            FieldDataFactory::build(FieldDataFactory::FIELD_TYPE_MESSAGE, new RequiredData(true)),
            FieldDataFactory::build(FieldDataFactory::FIELD_TYPE_AGREEMENT, new RequiredData(true)),
        );
    }
}
