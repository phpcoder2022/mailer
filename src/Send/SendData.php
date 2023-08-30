<?php

namespace Phpcoder2022\SimpleMailer\Send;

/**
 * @psalm-type TextsArr = array{mailSuccessHeader: string, mailFailHeader: string, formCompleteFailHeader: string, mailFailTitle: string, mailSuccessTextItem: string, mailFailTextItem: string}
 */

final class SendData
{
    public const DEFAULT_TEXTS_FILE_PATH = 'textsForSendForm.ini';

    /** @var TextsArr */
    private const TEXTS_ARR_STUB = [
        'mailSuccessHeader' => '',
        'mailFailHeader' => '',
        'formCompleteFailHeader' => '',
        'mailFailTitle' => '',
        'mailSuccessTextItem' => '',
        'mailFailTextItem' => '',
    ];

    public readonly string $mailSuccessHeader;
    public readonly string $mailFailHeader;
    public readonly string $formCompleteFailHeader;
    public readonly string $mailFailTitle;
    public readonly string $mailSuccessTextItem;
    public readonly string $mailFailTextItem;

    public function __construct(private readonly string $textsFilePath = self::DEFAULT_TEXTS_FILE_PATH)
    {
        if (!is_file($this->textsFilePath)) {
            $errorMessage = 'Файл настроек \"' . $this->textsFilePath .  '\" '
                . 'для создания объекта ' . __CLASS__ . ' не найден';
            throw new \UnexpectedValueException($errorMessage);
        }
        $texts = parse_ini_file($this->textsFilePath);
        $entriesArr = self::TEXTS_ARR_STUB;
        $notExistsKeys = [];
        foreach ($entriesArr as $key => $_) {
            $entriesArr[$key] = is_string($texts[$key]) ? $texts[$key] : '';
            if (!$entriesArr[$key]) {
                $notExistsKeys[] = $key;
            }
        }
        if ($notExistsKeys) {
            $errorMessage = "Тексты в файле \"$this->textsFilePath\": не хватает следующих ключей: "
                . join(', ', $notExistsKeys);
            throw new \UnexpectedValueException($errorMessage);
        }
        foreach ($entriesArr as $entryKey => $entryValue) {
            $this->{$entryKey} = $entryValue;
        }
    }
}
