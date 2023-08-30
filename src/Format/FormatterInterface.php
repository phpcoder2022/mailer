<?php

namespace Phpcoder2022\SimpleMailer\Format;

use Phpcoder2022\SimpleMailer\DTO\FieldsData;
use Phpcoder2022\SimpleMailer\Send\Sender;

/**
 * @psalm-import-type FormatFormDataResult from Sender
 */

interface FormatterInterface
{
    public const FIELD_NAME_TEMPLATE = 'FIELD';
    public const FIELD_NAME_PREG = '/' . self::FIELD_NAME_TEMPLATE . '/u';
    public const NUMBER_TEMPLATE = 'NUM';
    public const NUMBER_PREG = '/' . self::NUMBER_TEMPLATE . '/u';

    public function __construct(FieldsData $fieldsData);

    /** @return FormatFormDataResult */
    public function format(array $formData): array;
}
