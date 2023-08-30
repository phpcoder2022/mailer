<?php

namespace Phpcoder2022\SimpleMailer\DTO;

class RequiredData
{
    public function __construct(public readonly bool $onlyForOriginalKey)
    {
    }
}
