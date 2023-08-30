<?php

namespace Phpcoder2022\SimpleMailer\FieldsData;

final class FieldsDataFactory
{
    final private function __construct()
    {
    }

    public static function createFieldsDataInstance(): FieldsData
    {
        return AboutFormLandingFieldsData::createWithData();
    }
}
