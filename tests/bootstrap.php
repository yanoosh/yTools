<?php
require_once(__DIR__ . '/TestConfiguration.php');
require_once(__DIR__ . '/TestHelper.php');
$autoloader = __DIR__ . '/../autoloader.php';
if (file_exists($autoloader)) {
    require_once($autoloader);
}