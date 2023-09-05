<?php

namespace Phpcoder2022\SimpleMailer\Log;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LogFormatter
{
    protected const DEFAULT_TITLE_LENGTH = 36;
    protected const TITLE_TAIL_LENGTH = 3;

    public function __construct(protected readonly LoggerInterface $logger)
    {
    }

    public function write(mixed $data, string $level = LogLevel::INFO): void
    {
        $this->logger->log(
            $level,
            static::getSeparator(date('d.m.Y H:i:s') . ' ' . $level) . PHP_EOL
                . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL
                . static::getSeparator() . PHP_EOL
                . PHP_EOL
        );
    }

    protected static function getSeparator(string $title = ''): string
    {
        $titleLen = mb_strlen($title);
        $halfTitleLen = intval($titleLen / 2);
        $maxLen = $titleLen <= static::DEFAULT_TITLE_LENGTH - static::TITLE_TAIL_LENGTH * 2
            ? static::DEFAULT_TITLE_LENGTH
            : $titleLen + $titleLen % 2 + self::TITLE_TAIL_LENGTH * 2;
        $halfMaxLen = intval($maxLen / 2);
        return str_repeat('-', $halfMaxLen - ($halfTitleLen + $titleLen % 2))
            . $title
            . str_repeat('-', $halfMaxLen - $halfTitleLen);
    }
}
