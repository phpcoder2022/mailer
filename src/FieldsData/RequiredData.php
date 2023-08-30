<?php

namespace Phpcoder2022\SimpleMailer\FieldsData;

class RequiredData
{
    public function __construct(public readonly bool $onlyForOriginalKey)
    {
    }
}
