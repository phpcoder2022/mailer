<?php

use Phpcoder2022\SimpleMailer\Sender;

require_once './vendor/autoload.php';

$json = boolval(@$_POST['json']);
$resultData = Sender::sendForm(array_filter($_POST, fn ($key) => $key !== 'json', ARRAY_FILTER_USE_KEY), $json);
http_response_code($resultData['result'] ? 200 : ($resultData['formComplete'] ? 503 : 400));
if ($json) {
    header('Content-Type: application/json; charset=utf-8');
}
echo $resultData['message'];
