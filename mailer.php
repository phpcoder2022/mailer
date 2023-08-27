<?php

use Phpcoder2022\SimpleMailer\Sender;
use Phpcoder2022\SimpleMailer\AboutFormLandingFieldsData;

require_once './vendor/autoload.php';

$json = boolval(@$_POST['json']);
$sender = new Sender(AboutFormLandingFieldsData::createWithData());
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
