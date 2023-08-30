<?php

use Phpcoder2022\SimpleMailer\DependencyInjectionContainer;
use Phpcoder2022\SimpleMailer\Send\Sender;

require_once './vendor/autoload.php';

$json = boolval(@$_POST['json']);
$sender = (new DependencyInjectionContainer())->get(Sender::class);
$sender->sendForm(array_filter($_POST, fn ($key) => $key !== 'json', ARRAY_FILTER_USE_KEY));
http_response_code(
    $sender->getLastOperationResult() ? 200 : ($sender->getLastFormComplete() ? 503 : 400)
);
if ($json) {
    header('Content-Type: application/json; charset=utf-8');
    echo $sender->getResultAsJson();
    return;
}
echo $sender->getResultAsHtml();
