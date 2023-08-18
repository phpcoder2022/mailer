<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @psalm-import-type ErrorMessage from Mailer
 * @psalm-import-type ResultMessage from Mailer
 * @psalm-import-type FormatFormDataResult from Mailer
 */

class MailerTest extends TestCase
{
    protected const FORM_COMPLETE_TEXTS = [
        0 => 'К сожалению, отправить не удалось',
        1 => 'Успешно отправлено',
    ];
    protected const FORM_NOT_COMPLETE_TEXT = 'Форма неправильно заполнена';

    protected static function getMethod(string $name): \ReflectionMethod
    {
        return (new \ReflectionClass(Mailer::class))->getMethod($name);
    }

    #[DataProvider('sendFormProviderForLocalhost')]
    public function testSendForm(array $formData, bool $json, array $resultScheme): void
    {
        $actualResult = static::getMethod('sendForm')->invoke(null, $formData, $json);
        $this->assertEquals($resultScheme['result'], $actualResult['result']);
        $this->assertEquals($resultScheme['formComplete'], $actualResult['formComplete']);
        $jsonData = $json ? json_decode($actualResult['message'], true) : [];
        $textContent = !$json ? html_entity_decode($actualResult['message']) : '';
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
            foreach ($resultScheme['errors'] as $error) {
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
                    $this->assertStringContainsString($error['message'], $textContent, $desc);
                }
            }
        }
    }

    public static function sendFormProviderForLocalhost(): array
    {
        return array_reduce(
            static::formatFormDataProvider(),
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

    #[DataProvider('formatFormDataProvider')]
    public function testFormatFormData(array $formData, array $result): void
    {
        $this->assertEquals($result, static::getMethod('formatFormData')->invoke(null, $formData));
    }

    public static function formatFormDataProvider(): array
    {
        $mainArr = [
            [
                [
                    'name' => 'Николай',
                    'agreement' => 'on',
                    'message' => 'Хочу заказать радио',
                    'email' => 'nikky@gmail.com',
                ],
                [
                    'mode' => 'mail',
                    'message' => '<table border="1">'
                        . '<tr data-id="name"><td><b>Имя</b></td><td>Николай</td></tr>'
                        . '<tr data-id="email"><td><b>Email</b></td><td>nikky@gmail.com</td></tr>'
                        . '<tr data-id="message"><td><b>Сообщение</b></td><td>Хочу заказать радио</td></tr>'
                        . '<tr data-id="agreement">'
                            . '<td><b>Согласие на обработку персональных данных</b></td><td>Да</td>'
                        .'</tr>'
                        . '</table>',
                ]
            ],
        ];
        $firstProgramVersionInputAndOutputData = json_decode(file_get_contents('./testOutput.json'), true);
        foreach ($firstProgramVersionInputAndOutputData as $item) {
            $mainArr[] = [$item['args'][0], $item['output']];
        }
        return $mainArr;
    }
}
