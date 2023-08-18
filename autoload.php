<?php
spl_autoload_register(function ($className) {
    $arr = explode('\\', $className);
    require_once end($arr) . '.php';
});
