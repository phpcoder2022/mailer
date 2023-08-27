<?php

namespace Phpcoder2022\SimpleMailer;

/**
 * @psalm-type MailAddressesData = array{To: string, Subject: string, From: string, Reply-To: string}
 */

class Mailer
{
    protected const ADDRESSES_DATA_PATH = 'mails.txt';

    final private function __construct()
    {
    }

    public static function sendMail(string $html): bool
    {
        if (self::isLocalhost()) {
            return false;
        }
        $mailAddressesData = self::getMailAddressesAndSubject();
        return mail(
            $mailAddressesData['To'],
            $mailAddressesData['Subject'],
            preg_replace(
                ['/></u', '/\s+/u', '/([^\s;]{50}|;)(\S)/u'],
                [">\r\n<", "\r\n", "\\1<span></span\r\n>\\2"],
                $html
            ) . "\r\n",
            [
                'From' => $mailAddressesData['From'],
                'Reply-To' => $mailAddressesData['Reply-To'],
                'Content-Type' => 'text/html; charset=utf-8',
                'X-Sender' => 'PHP/' . phpversion(),
            ],
        );
    }

    protected static function isLocalhost(): bool
    {
        /** @psalm-suppress PossiblyUndefinedArrayOffset */
        return !($_SERVER['SERVER_NAME'] ?? '')
            || !($_SERVER['SERVER_ADDR'] ?? '')
            || $_SERVER['SERVER_NAME'] === 'localhost'
            || $_SERVER['SERVER_ADDR'] === '127.0.0.1';
    }

    /**
     * @return MailAddressesData
     */
    protected static function getMailAddressesAndSubject(): array
    {
        $lineArr = is_file(static::ADDRESSES_DATA_PATH)
            ? file(static::ADDRESSES_DATA_PATH, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES)
            : [];
        $resultArr = ['To' => '', 'Subject' => '', 'From' => '', 'Reply-To' => ''];
        foreach ($resultArr as $entryKey => $_) {
            for ($i = 0; $i < count($lineArr); $i++) {
                if (preg_match('/^\s*#\s*' . $entryKey . ':\s*(.*?)\s*/iu', $lineArr[$i])) {
                    $resultArr[$entryKey] = trim($lineArr[$i + 1] ?? '');
                    break;
                }
            }
            if (!$resultArr[$entryKey]) {
                throw new \UnexpectedValueException("Ключ $entryKey не определён в файле " . static::ADDRESSES_DATA_PATH);
            }
        }
        return $resultArr;
    }
}
