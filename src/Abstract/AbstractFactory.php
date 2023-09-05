<?php

namespace Phpcoder2022\SimpleMailer\Abstract;

use Phpcoder2022\SimpleMailer\DependencyInjectionContainer;

abstract class AbstractFactory
{
    public const CLASS_LIST = [];

    final private function __construct()
    {
    }

    public static function build(
        DependencyInjectionContainer $di,
        string $classListKey = '',
    ): object {
        $classListKey = mb_strlen($classListKey) ? $classListKey : static::getDefaultKey();
        static::validate($classListKey);
        return static::buildConcreteRealisation($di, $classListKey);
    }

    abstract public static function getDefaultKey(): string;

    protected static function validate(string $classListKey): void
    {
        if (!isset(static::CLASS_LIST[$classListKey])) {
            throw new \InvalidArgumentException(
                sprintf("У фабрики %s нет класса с ключом \"$classListKey\"", static::class)
            );
        }
    }

    protected static function buildConcreteRealisation(DependencyInjectionContainer $di, string $classListKey): object
    {
        return $di->get(static::CLASS_LIST[$classListKey]);
    }

    /** @param class-string $expectedClassName */
    protected static function getErrorMessage(string $expectedClassName, mixed $actualResult): string
    {
        return sprintf(
            "Ошибка в фабрике: ожидаемый тип: $expectedClassName, полученный: %s",
            is_object($actualResult) ? get_class($actualResult) : gettype($actualResult),
        );
    }
}
