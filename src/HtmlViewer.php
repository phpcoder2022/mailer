<?php

namespace Phpcoder2022\SimpleMailer;

class HtmlViewer
{
    public const DEFAULT_TEMPLATE = './info.html';

    final private function __construct()
    {
    }

    /**
     * @param string $title
     * @param string $header
     * @param array{message: string}[] $textItems
     * @param bool $successFormSent
     * @param string $templatePath
     * @return string
     */
    public static function loadTemplate(
        string $title,
        string $header,
        array $textItems,
        bool $successFormSent,
        string $templatePath = ''
    ): string {
        $replaced = preg_replace(
            [
                '/#TITLE#/u',
                '/#HEADER#/u',
                '/(<!--\s*)?##SUCCESS_FORM_SEN[TD](\s*-->)?(.*?)(<!--\s*)?##(\s*-->)?/us',
                '/#HTTP_REFER{1,2}ER#/u',
            ],
            [$title, $header, $successFormSent ? '\\3' : '', ($_SERVER['HTTP_REFERER'] ?? '') ?: '/'],
            file_get_contents($templatePath ?: static::DEFAULT_TEMPLATE)
        );
        $dom = new \DOMDocument();
        $dom->encoding = 'utf-8';
        @$dom->loadHtml($replaced);
        $textItemsList = $dom->getElementById('text-items');
        $textItemsList = static::getCheckedDomElement($textItemsList, "Не найден элемент-список с id=\"text-items\"");
        $templateItem = $textItemsList->firstElementChild;
        $templateItem = static::getCheckedDomElement($templateItem, "Список #text-items не содержит элементов");
        $textItemsList->textContent = '';
        foreach ($textItems as $textItem) {
            $newItem = $templateItem->cloneNode();
            $newItem->textContent = $textItem['message'];
            $textItemsList->appendChild($newItem);
        }
        return '<!doctype html>' . PHP_EOL
            . preg_replace('/<\/(meta|link|br|hr|input)>/ui', '', $dom->documentElement->C14N());
    }

    protected static function getCheckedDomElement(?\DOMElement $element, string $message): \DOMElement
    {
        if ($element instanceof \DOMElement) {
            return $element;
        } else {
            throw new \LogicException("Шаблон некорректен: $message");
        }
    }
}
