<?php

use Phpcoder2022\SimpleMailer\Mailer;

require_once './vendor/autoload.php';

if (!isset($argv)) {
    return;
}
$input = json_decode(file_get_contents('testInput.json'), true);
$output = [];
foreach ($input as ['method' => $method, 'args' => $args]) {
    $output[] = [
        'method' => $method,
        'args' => $args,
        'output' => (new \ReflectionClass(Mailer::class))->getMethod($method)->invoke(null, ...$args),
    ];
}
file_put_contents('testOutput.json', json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
