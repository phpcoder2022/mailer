<?php

namespace Phpcoder2022\SimpleMailer\Tests;

use Phpcoder2022\SimpleMailer\Sender;
use Phpcoder2022\SimpleMailer\AboutFormLandingFieldsData;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SenderTest extends TestCase
{
    protected const FORM_COMPLETE_TEXTS = [
        0 => 'К сожалению, отправить не удалось',
        1 => 'Успешно отправлено',
    ];
    protected const FORM_NOT_COMPLETE_TEXT = 'Форма неправильно заполнена';

    protected Sender $sender;

    #[DataProvider('sendFormProviderForLocalhost')]
    public function testSendForm(array $formData, bool $json, array $resultScheme): void
    {
        $this->sender ??= new Sender(AboutFormLandingFieldsData::createWithData());
        $this->sender->sendForm($formData);
        $actualResult = [
            'result' => $this->sender->getLastOperationResult(),
            'formComplete' => $this->sender->getLastFormComplete(),
            'message' => ($getResultAs = [$this->sender, $json ? 'getResultAsJson' : 'getResultAsHtml'])()
        ];
        $this->assertEquals($resultScheme['result'], $actualResult['result']);
        $this->assertEquals($resultScheme['formComplete'], $actualResult['formComplete']);
        if ($resultScheme['formComplete']) {
            $findResult = array_reduce(
                static::FORM_COMPLETE_TEXTS,
                static fn (bool $accum, string $message) =>
                    $accum || str_contains($actualResult['message'], $message),
                false,
            );
            $this->assertTrue($findResult, 'Сообщение об отправке (успех/неудача)');
        } else {
            $this->assertStringContainsString(static::FORM_NOT_COMPLETE_TEXT, $actualResult['message']);
            $this->checkErrorMessages($resultScheme['errors'], $actualResult['message'], $json);
        }
    }

    protected function checkErrorMessages(array $errors, string $rawTextContent, bool $json): void
    {
        $jsonData = $json ? json_decode($rawTextContent, true) : [];
        $htmlContent = !$json ? html_entity_decode($rawTextContent) : '';
        foreach ($errors as $error) {
            $desc = 'Описание ошибки (' . ($json ? 'json' : 'html') . '): ' . $error['message'];
            if ($json) {
                $textItemsFindResult = false;
                foreach ($jsonData['textItems'] as $textItem) {
                    if ($textItem['message'] === $error['message']) {
                        $textItemsFindResult = true;
                        break;
                    }
                }
                $this->assertTrue($textItemsFindResult, $desc);
            } else {
                $this->assertStringContainsString($error['message'], $htmlContent, $desc);
            }
        }
    }

    public static function sendFormProviderForLocalhost(): array
    {
        return array_reduce(
            FormatterTest::formatProvider(),
            static function (array $accum, array $params): array {
                $result = false;
                $formComplete = $params[1]['mode'] === 'mail';
                $errors = $formComplete ? [] : $params[1]['messages'];
                foreach ([false, true] as $json) {
                    $accum[] = [
                        $params[0],
                        $json,
                        compact('result', 'errors', 'formComplete'),
                    ];
                }
                return $accum;
            },
            [],
        );
    }
}
