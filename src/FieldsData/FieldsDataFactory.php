<?php

namespace Phpcoder2022\SimpleMailer\FieldsData;

use Phpcoder2022\SimpleMailer\Abstract\AbstractFactory;
use Phpcoder2022\SimpleMailer\DependencyInjectionContainer;

final class FieldsDataFactory extends AbstractFactory
{
    public const ABOUT_FORM = 'about';
    public const CLASS_LIST = [
        self::ABOUT_FORM => AboutFormLandingFieldsData::class,
    ];

    public static function build(DependencyInjectionContainer $di, string $classListKey = ''): FieldsData
    {
        $result = parent::build($di, $classListKey);
        return $result instanceof FieldsData
            ? $result
            : throw new \LogicException(self::getErrorMessage(FieldsData::class, $result));
    }

    public static function getDefaultKey(): string
    {
        return self::ABOUT_FORM;
    }

    protected static function buildConcreteRealisation(
        DependencyInjectionContainer $di,
        string $classListKey
    ): FieldsData {
        return ($factoryMethod = [self::CLASS_LIST[$classListKey], 'createWithData'])();
    }
}
