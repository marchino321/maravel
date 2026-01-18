<?php




define('CLI_MODE', true); // 🔑 QUESTA RIGA È FONDAMENTALE

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/ConfigFiles/bootstrap.cli.php';

if (php_sapi_name() !== 'cli') {
  exit("❌ Questo script può essere eseguito solo da CLI.\n");
}

use App\Config;
use App\Console\Kernel;

$kernel = new Kernel();
$kernel->handle($_SERVER['argv']);
