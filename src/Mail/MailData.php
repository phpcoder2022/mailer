<?php

namespace Phpcoder2022\SimpleMailer\Mail;

final class MailData
{
    private const DEFAULT_ADDRESSES_DATA_PATH = 'mails.txt';

    private string $to = '';
    private string $subject = '';
    private string $from = '';
    private string $replyTo = '';

    public function __construct(private readonly string $addressesDataPath = self::DEFAULT_ADDRESSES_DATA_PATH)
    {
        $lineArr = is_file($this->addressesDataPath)
            ? file($this->addressesDataPath, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES)
            : [];
        $entriesArr = ['To' => '', 'Subject' => '', 'From' => '', 'Reply-To' => ''];
        $errors = [];
        foreach ($entriesArr as $entryKey => $_) {
            for ($i = 0; $i < count($lineArr); $i++) {
                if (preg_match('/^\s*#\s*' . $entryKey . ':\s*(.*?)\s*/iu', $lineArr[$i])) {
                    $entriesArr[$entryKey] = trim($lineArr[$i + 1] ?? '');
                    break;
                }
            }
            if (!$entriesArr[$entryKey]) {
                $errors[] = "Ключ $entryKey не определён в файле " . $this->addressesDataPath;
            }
        }
        if ($errors) {
            throw new \UnexpectedValueException(join(PHP_EOL, $errors));
        }
        foreach ($entriesArr as $entryKey => $entryValue) {
            $this->{self::translateEntryKeyToObjectKey($entryKey)} = $entryValue;
        }
        /** @psalm-suppress RawObjectIteration : здесь проверяется инициализация свойств объекта, поэтому нужно Raw */
        foreach ($this as $objectKey => $objectValue) {
            if (is_string($objectValue) && !strlen($objectValue)) {
                throw new \LogicException("Ключ \"$objectKey\" не инициализирован в объекте " . self::class);
            }
        }
    }

    private static function translateEntryKeyToObjectKey(string $entryKey): string
    {
        return strtolower($entryKey[0]). str_replace('-', '', substr($entryKey, 1));
    }

    public function getTo(): string
    {
        return $this->to;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function getReplyTo(): string
    {
        return $this->replyTo;
    }
}
