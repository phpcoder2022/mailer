<?php

namespace Phpcoder2022\SimpleMailer;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class DependencyInjectionContainer implements ContainerInterface
{
    private array $lazyLoads = [];

    public function __construct()
    {
        $this->lazyLoads = [
            Sender::class => fn (): Sender => new Sender(
                $this->get(FormatterInterface::class),
                $this->get(LoggerInterface::class),
                $this->get(HtmlViewer::class),
                $this->get(Mailer::class),
            ),
            FormatterInterface::class => fn (): FormatterInterface => new Formatter(
                $this->get(FieldsData::class)
            ),
            LoggerInterface::class => fn (): LoggerInterface => new Logger(),
            Mailer::class => fn (): string => Mailer::class,
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
