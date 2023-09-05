<?php

use Phpcoder2022\SimpleMailer\DependencyInjectionContainer;
use Phpcoder2022\SimpleMailer\Send\Sender;

require_once './vendor/autoload.php';

$responseFormat = @$_POST['json'] ? 'json' : '';
$sender = (new DependencyInjectionContainer($responseFormat))->get(Sender::class);
$sendResponseResult = $sender->sendForm(array_filter($_POST, fn ($key) => $key !== 'json', ARRAY_FILTER_USE_KEY));
http_response_code(
    $sendResponseResult->operationResult ? 200 : ($sendResponseResult->formComplete ? 503 : 400)
);
if ($responseFormat) {
    header('Content-Type: application/json; charset=utf-8');
}
echo $sendResponseResult->resultAsString;
