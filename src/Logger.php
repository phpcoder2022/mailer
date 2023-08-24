<?php

namespace Phpcoder2022\SimpleMailer;

class Logger
{
    public const DEFAULT_LOG_PATH = 'forms.log';

    public function __construct(protected string $logPath = '')
    {
        if (!$this->logPath) {
            $this->logPath = static::DEFAULT_LOG_PATH;
        }
    }

    public function write(mixed $data): void
    {
        $logDesc = fopen($this->logPath, 'a');
        if (!flock($logDesc, LOCK_EX)) {
            return;
        }
        fwrite(
            $logDesc,
            static::getLogTitle(date('d.m.Y H:i:s')) . PHP_EOL
            . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL
            . static::getLogTitle() . PHP_EOL
            . PHP_EOL
        );
        fclose($logDesc);
    }

    protected static function getLogTitle(string $title = ''): string
    {
        $maxLen = 26;
        $titleLen = mb_strlen($title);
        $halfTitleLen = intval($titleLen / 2);
        return str_repeat('-', $maxLen / 2 - ($halfTitleLen + $titleLen % 2))
            . $title
            . str_repeat('-', $maxLen / 2 - $halfTitleLen);
    }
}
