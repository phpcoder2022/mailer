<?php

namespace Phpcoder2022\SimpleMailer\FieldsData;

final class FieldsDataFabric
{
    final private function __construct()
    {
    }

    public static function createFieldsDataInstance(): FieldsData
    {
        return AboutFormLandingFieldsData::createWithData();
    }
}
