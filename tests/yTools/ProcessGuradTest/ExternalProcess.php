<?php
require_once(__DIR__ . '/../../bootstrap.php');
require_once('PHPUnit/Autoload.php');
require_once(__DIR__ . '/ProcessGuardTest.php');
echo yTools\ProcessGuardTest::runNewProcess(@$argv[1], array_slice($argv, 2));
exit(0);