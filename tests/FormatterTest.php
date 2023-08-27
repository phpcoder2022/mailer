<?php

namespace Phpcoder2022\SimpleMailer\Tests;

use Phpcoder2022\SimpleMailer\AboutFormLandingFieldsData;
use Phpcoder2022\SimpleMailer\Formatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FormatterTest extends TestCase
{
    #[DataProvider('formatProvider')]
    public function testFormat(array $formData, array $result): void
    {
        $actualResult = (new Formatter(AboutFormLandingFieldsData::createWithData()))->format($formData);
        $this->assertEquals($result, $actualResult);
    }

    public static function formatProvider(): array
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
                        . '</tr>'
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
