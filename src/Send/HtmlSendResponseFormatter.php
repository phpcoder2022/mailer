<?php

namespace Phpcoder2022\SimpleMailer\Send;

use Phpcoder2022\SimpleMailer\Log\LogFormatter;
use Phpcoder2022\SimpleMailer\ProcessHtml\HtmlViewer;

/**
 * @psalm-import-type TextItems from SendResponseFormatter
 */

final class HtmlSendResponseFormatter extends SendResponseFormatter
{
    public function __construct(
        SendTexts $sendTexts,
        private readonly HtmlViewer $htmlViewer,
        private readonly LogFormatter $logger
    ) {
        parent::__construct($sendTexts);
    }

    /**
     * @param string $header
     * @param string $title
     * @param bool $formComplete
     * @param TextItems $textItems
     * @return string
     */
    protected function getResultAsString(string $header, string $title, bool $formComplete, array $textItems): string
    {
        $this->logger->write(__METHOD__);
        $messageItems = array_map(fn ($subArr) => ['message' => $subArr['message']], $textItems);
        return $this->htmlViewer->loadTemplate($title, $header, $messageItems, $formComplete);
    }
}
