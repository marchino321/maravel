<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/ConfigFiles/bootstrap.php';

if (php_sapi_name() !== 'cli') {
  exit("❌ Questo script può essere eseguito solo da CLI.\n");
}


use App\Console\Kernel;

$kernel = new Kernel();
$kernel->handle($_SERVER['argv']);
