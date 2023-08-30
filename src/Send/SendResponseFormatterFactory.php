<?php

namespace Phpcoder2022\SimpleMailer\Send;

use Phpcoder2022\SimpleMailer\DependencyInjectionContainer;

final class SendResponseFormatterFactory
{
    final private function __construct()
    {
    }

    public static function createSendResponseFormatterInstance(
        DependencyInjectionContainer $di,
        bool $json
    ): SendResponseFormatter {
        return $di->get($json ? JsonSendResponseFormatter::class : HtmlSendResponseFormatter::class);
    }
}
