<?php

namespace Phpcoder2022\SimpleMailer\Send;

/**
 * @psalm-import-type ListOfErrorMessages from Sender
 * @psalm-type TextItems = ListOfErrorMessages | non-empty-list<array{message: string}>
 */

abstract class SendResponseFormatter
{
    public function __construct(
        protected readonly SendTexts $sendTexts
    ) {
    }

    /**
     * @param bool $operationResult
     * @param bool $formComplete
     * @param ListOfErrorMessages|null $textItems
     * @return SendResponseResult
     */
    public function getResult(bool $operationResult, bool $formComplete, ?array $textItems): SendResponseResult
    {
        if ($formComplete) {
            $mailMessage = $this->sendTexts->{'mail' . ($operationResult ? 'Success' : 'Fail') . 'Header'};
        }
        $header = $mailMessage ?? $this->sendTexts->formCompleteFailHeader;
        $textItems ??= [$operationResult
            ? ['message' => $this->sendTexts->mailSuccessTextItem]
            : ['message' => $this->sendTexts->mailFailTextItem]
        ];
        $title = $formComplete && !$operationResult
            ? $this->sendTexts->mailFailTitle
            : $header;
        return new SendResponseResult(
            $operationResult,
            $formComplete,
            $this->getResultAsString($header, $title, $formComplete, $textItems),
        );
    }

    /**
     * @param string $header
     * @param string $title
     * @param bool $formComplete
     * @param TextItems $textItems
     * @return string
     */
    abstract protected function getResultAsString(
        string $header,
        string $title,
        bool $formComplete,
        array $textItems
    ): string;
}
