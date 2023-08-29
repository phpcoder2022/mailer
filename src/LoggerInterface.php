<?php

namespace Phpcoder2022\SimpleMailer;

interface LoggerInterface
{
    public function __construct(string $logPath = '');

    public function write(mixed $data): void;
}
