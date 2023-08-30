<?php

namespace Phpcoder2022\SimpleMailer;

use Phpcoder2022\SimpleMailer\FieldsData\AboutFormLandingFieldsData;
use Phpcoder2022\SimpleMailer\FieldsData\FieldsData;
use Phpcoder2022\SimpleMailer\Format\Formatter;
use Phpcoder2022\SimpleMailer\Format\FormatterInterface;
use Phpcoder2022\SimpleMailer\Log\Logger;
use Phpcoder2022\SimpleMailer\Mail\MailData;
use Phpcoder2022\SimpleMailer\Mail\Mailer;
use Phpcoder2022\SimpleMailer\ProcessHtml\HtmlViewer;
use Phpcoder2022\SimpleMailer\Send\HtmlSendResponseFormatter;
use Phpcoder2022\SimpleMailer\Send\JsonSendResponseFormatter;
use Phpcoder2022\SimpleMailer\Send\SendResponseFormatter;
use Phpcoder2022\SimpleMailer\Send\SendTexts;
use Phpcoder2022\SimpleMailer\Send\Sender;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class DependencyInjectionContainer implements ContainerInterface
{
    private array $lazyLoads = [];

    public function __construct(private readonly bool $json)
    {
        $this->lazyLoads = [
            Sender::class => fn (): Sender => new Sender(
                $this->get(FormatterInterface::class),
                $this->get(LoggerInterface::class),
                $this->get(Mailer::class),
                $this->get(SendResponseFormatter::class)
            ),
            SendTexts::class => fn (): SendTexts => new SendTexts(),
            SendResponseFormatter::class => fn (): SendResponseFormatter =>
                $this->get($this->json ? JsonSendResponseFormatter::class : HtmlSendResponseFormatter::class),
            HtmlSendResponseFormatter::class => fn (): HtmlSendResponseFormatter => new HtmlSendResponseFormatter(
                $this->get(SendTexts::class),
                $this->get(HtmlViewer::class),
                $this->get(Logger::class),
            ),
            JsonSendResponseFormatter::class => fn (): JsonSendResponseFormatter => new JsonSendResponseFormatter(
                $this->get(SendTexts::class),
                $this->get(Logger::class),
            ),
            FormatterInterface::class => fn (): FormatterInterface => new Formatter(
                $this->get(FieldsData::class),
            ),
            LoggerInterface::class => fn (): LoggerInterface => new Logger(),
            Mailer::class => fn (): Mailer => new Mailer(
                $this->get(MailData::class)
            ),
            MailData::class => fn (): MailData => new MailData(),
            FieldsData::class => fn (): FieldsData => AboutFormLandingFieldsData::createWithData(),
            HtmlViewer::class => fn (): HtmlViewer => new HtmlViewer(),
        ];
        $this->lazyLoads[Formatter::class] = &$this->lazyLoads[FormatterInterface::class];
        $this->lazyLoads[Logger::class] = &$this->lazyLoads[LoggerInterface::class];
    }

    public function get(string $id): mixed
    {
        return $this->has($id)
            ? ($this->lazyLoads[$id])()
            : throw new \UnexpectedValueException("Элемент контейнера \"$id\" не найден");
    }

    public function has(string $id): bool
    {
        return isset($this->lazyLoads[$id]);
    }
}
