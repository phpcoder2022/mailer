<?php

namespace Phpcoder2022\SimpleMailer\ProcessHtml;

final class HtmlViewer
{
    public const DEFAULT_TEMPLATE = './info.html';

    public function __construct(private readonly string $templatePath = self::DEFAULT_TEMPLATE)
    {
    }

    /**
     * @param string $title
     * @param string $header
     * @param array{message: string}[] $textItems
     * @param bool $successFormSent
     * @return string
     */
    public function loadTemplate(
        string $title,
        string $header,
        array $textItems,
        bool $successFormSent,
    ): string {
        $replaced = preg_replace(
            [
                '/#TITLE#/u',
                '/#HEADER#/u',
                '/(<!--\s*)?##SUCCESS_FORM_SEN[TD](\s*-->)?(.*?)(<!--\s*)?##(\s*-->)?/us',
                '/#HTTP_REFER{1,2}ER#/u',
            ],
            [$title, $header, $successFormSent ? '\\3' : '', ($_SERVER['HTTP_REFERER'] ?? '') ?: '/'],
            file_get_contents($this->templatePath)
        );
        $dom = new \DOMDocument();
        $dom->encoding = 'utf-8';
        @$dom->loadHtml($replaced);
        $textItemsList = $dom->getElementById('text-items');
        $textItemsList = self::getCheckedDomElement($textItemsList, "Не найден элемент-список с id=\"text-items\"");
        $templateItem = $textItemsList->firstElementChild;
        $templateItem = self::getCheckedDomElement($templateItem, "Список #text-items не содержит элементов");
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
