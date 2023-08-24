<?php

namespace Phpcoder2022\SimpleMailer;

class RequiredData
{
    public function __construct(public readonly bool $onlyForOriginalKey)
    {
    }
}
