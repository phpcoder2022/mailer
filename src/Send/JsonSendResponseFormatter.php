<?php

namespace Phpcoder2022\SimpleMailer\Send;

use Phpcoder2022\SimpleMailer\Log\LogFormatter;

/**
 * @psalm-import-type TextItems from SendResponseFormatter
 */

final class JsonSendResponseFormatter extends SendResponseFormatter
{
    public function __construct(
        SendTexts $sendTexts,
        private readonly LogFormatter $logger,
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
        return json_encode(['header' => $header, 'textItems' => $textItems], JSON_UNESCAPED_UNICODE);
    }
}
