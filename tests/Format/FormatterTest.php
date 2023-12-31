<?php

namespace Phpcoder2022\SimpleMailer\Tests\Format;

use Phpcoder2022\SimpleMailer\DependencyInjectionContainer;
use Phpcoder2022\SimpleMailer\Format\Formatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FormatterTest extends TestCase
{
    private DependencyInjectionContainer $container;
    private Formatter $formatter;

    #[DataProvider('formatProvider')]
    public function testFormat(array $formData, array $result): void
    {
        $this->container ??= new DependencyInjectionContainer();
        $this->formatter ??= $this->container->get(Formatter::class);
        $actualResult = $this->formatter->format($formData);
        $this->assertEquals($result, $actualResult);
    }

    public static function formatProvider(): array
    {
        $mainArr = [self::getFirstFormatTestCase()];
        $firstProgramVersionInputAndOutputData = json_decode(file_get_contents('./testOutput.json'), true);
        foreach ($firstProgramVersionInputAndOutputData as $item) {
            $mainArr[] = [$item['args'][0], $item['output']];
        }
        return $mainArr;
    }

    private static function getFirstFormatTestCase(): array
    {
        return [
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
        ];
    }
}
