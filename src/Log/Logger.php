<?php

namespace Phpcoder2022\SimpleMailer\Log;

use Psr\Log\AbstractLogger;

final class Logger extends AbstractLogger
{
    public const DEFAULT_LOG_PATH = 'forms.log';
    private const DEFAULT_TITLE_LENGTH = 36;
    private const TITLE_TAIL_LENGTH = 3;

    public function __construct(private string $logPath = '')
    {
        if (!$this->logPath) {
            $this->logPath = self::DEFAULT_LOG_PATH;
        }
    }

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->write(strval($level), strval($message) ?: $context);
    }

    private function write(string $level, mixed $data): void
    {
        $logDesc = fopen($this->logPath, 'a');
        if (!flock($logDesc, LOCK_EX)) {
            return;
        }
        fwrite(
            $logDesc,
            self::getSeparator(date('d.m.Y H:i:s') . ' ' . $level) . PHP_EOL
            . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL
            . self::getSeparator() . PHP_EOL
            . PHP_EOL
        );
        fclose($logDesc);
    }

    private static function getSeparator(string $title = ''): string
    {
        $titleLen = mb_strlen($title);
        $halfTitleLen = intval($titleLen / 2);
        $maxLen = $titleLen <= self::DEFAULT_TITLE_LENGTH - self::TITLE_TAIL_LENGTH * 2
            ? self::DEFAULT_TITLE_LENGTH
            : $titleLen + $titleLen % 2 + self::TITLE_TAIL_LENGTH * 2;
        $halfMaxLen = intval($maxLen / 2);
        return str_repeat('-', $halfMaxLen - ($halfTitleLen + $titleLen % 2))
            . $title
            . str_repeat('-', $halfMaxLen - $halfTitleLen);
    }
}
