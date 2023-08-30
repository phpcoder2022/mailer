<?php

namespace Phpcoder2022\SimpleMailer\FieldsData;

use Phpcoder2022\SimpleMailer\Format\GrammaticalGender;

final class FieldData
{
    public const DEFAULT_MAX_LENGTH = 500;
    public const NO_MAX_LENGTH = 0;
    public const VALIDATE_AS_JS_PLUGIN = '/^
            [a-z\-+\/*!?=\#$%^&0-9]
            ([a-z\.\-+\/*!?=\#$%^&0-9]*[a-z\-+\/*!?=\#$%^&0-9])?
            @
            [a-z\-0-9]
            ([a-z\-\.0-9]*[a-z\-0-9])?
            \.
            [a-z]{2,}
        $|^$/ix';

    /** @var ?callable $validateCallback */
    public readonly mixed $validateCallback;

    public function __construct(
        public readonly string            $key,
        public readonly string            $name,
        public readonly GrammaticalGender $grammaticalGender,
        public readonly int               $maxLength = self::DEFAULT_MAX_LENGTH,
        public readonly string            $validateRegExp = '',
        ?callable                         $validateCallback = null,
        public readonly ?RequiredData     $required = null,
        public readonly string            $errorMessage = '',
        public readonly bool              $errorMessageAsNotExists = false,
        public readonly string            $replacementValue = '',
    ) {
        $this->validateCallback = $validateCallback;
    }
}
