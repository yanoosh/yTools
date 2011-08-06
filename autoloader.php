<?php
$src = realpath(__DIR__ . '/src');
spl_autoload_register(function ($name) use ($src) {
    $path = $src . '/' .
        str_replace(array('\\'), array(DIRECTORY_SEPARATOR), $name) .
        '.php';
    if (file_exists($path)) {
        require_once($path);
        return true;
    } else {
        return false;
    }
});