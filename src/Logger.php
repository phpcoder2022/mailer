<?php

namespace Phpcoder2022\SimpleMailer;

final class Logger implements LoggerInterface
{
    public const DEFAULT_LOG_PATH = 'forms.log';

    public function __construct(private string $logPath = '')
    {
        if (!$this->logPath) {
            $this->logPath = self::DEFAULT_LOG_PATH;
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
            self::getSeparator(date('d.m.Y H:i:s')) . PHP_EOL
            . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL
            . self::getSeparator() . PHP_EOL
            . PHP_EOL
        );
        fclose($logDesc);
    }

    private static function getSeparator(string $title = ''): string
    {
        $maxLen = 26;
        $titleLen = mb_strlen($title);
        $halfTitleLen = intval($titleLen / 2);
        return str_repeat('-', $maxLen / 2 - ($halfTitleLen + $titleLen % 2))
            . $title
            . str_repeat('-', $maxLen / 2 - $halfTitleLen);
    }
}
