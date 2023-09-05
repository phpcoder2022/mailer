<?php

namespace Phpcoder2022\SimpleMailer\FieldsData;

use Phpcoder2022\SimpleMailer\DependencyInjectionContainer;

final class FieldsDataFactory
{
    public const FORM_TYPES = [
        'about' => AboutFormLandingFieldsData::class,
    ];

    final private function __construct()
    {
    }

    public static function createFieldsDataInstance(
        DependencyInjectionContainer $di,
        string $formType = 'about',
    ): FieldsData {
        if (!isset(self::FORM_TYPES[$formType])) {
            throw new \InvalidArgumentException("У фабрики нет класса с ключём \"$formType\"");
        }
        return ($factoryMethod = [self::FORM_TYPES[$formType] , 'createWithData'])();
    }
}
