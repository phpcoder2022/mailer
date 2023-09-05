<?php

namespace Phpcoder2022\SimpleMailer\Log;

use Psr\Log\AbstractLogger;

final class Logger extends AbstractLogger
{
    public const DEFAULT_LOG_PATH = 'forms.log';

    public function __construct(private readonly string $logPath = self::DEFAULT_LOG_PATH)
    {
    }

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $logDesc = fopen($this->logPath, 'a');
        if (!flock($logDesc, LOCK_EX)) {
            return;
        }
        fwrite($logDesc, strval($message));
        fclose($logDesc);
    }
}
