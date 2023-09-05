<?php

namespace Phpcoder2022\SimpleMailer\Send;

use Phpcoder2022\SimpleMailer\Abstract\AbstractFactory;
use Phpcoder2022\SimpleMailer\DependencyInjectionContainer;

final class SendResponseFormatterFactory extends AbstractFactory
{
    public const HTML = 'html';
    public const JSON = 'json';
    public const CLASS_LIST = [
        self::HTML => HtmlSendResponseFormatter::class,
        self::JSON => JsonSendResponseFormatter::class,
    ];

    public static function build(DependencyInjectionContainer $di, string $classListKey = ''): SendResponseFormatter
    {
        $result = parent::build($di, $classListKey);
        return $result instanceof SendResponseFormatter
            ? $result
            : throw new \LogicException(self::getErrorMessage(SendResponseFormatter::class, $result));
    }

    public static function getDefaultKey(): string
    {
        return self::HTML;
    }
}
