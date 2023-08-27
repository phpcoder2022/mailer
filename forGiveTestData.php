<?php

use Phpcoder2022\SimpleMailer\Formatter;
use Phpcoder2022\SimpleMailer\AboutFormLandingFieldsData;

require_once './vendor/autoload.php';

if (!isset($argv)) {
    return;
}
$input = json_decode(file_get_contents('testInput.json'), true);
$output = [];
$formatter = new Formatter(AboutFormLandingFieldsData::createWithData());
foreach ($input as ['method' => $method, 'args' => $args]) {
    $output[] = [
        'method' => $method,
        'args' => $args,
        'output' => $formatter->format($args[0]),
    ];
}
file_put_contents('testOutput.json', json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
