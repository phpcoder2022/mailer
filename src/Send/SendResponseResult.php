<?php

namespace Phpcoder2022\SimpleMailer\Send;

class SendResponseResult
{
    public function __construct(
        public readonly bool $operationResult,
        public readonly bool $formComplete,
        public readonly string $resultAsString,
    ) {
    }
}
