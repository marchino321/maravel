<?php

declare(strict_types=1);

use App\Config;
use App\Debug;
use Core\Error;

// ⚠️ modalità CLI
if (!defined('CLI_MODE')) {
  define('CLI_MODE', true);
}

// niente sessioni
date_default_timezone_set('Europe/Rome');

// error reporting pieno
ini_set('display_errors', '1');
error_reporting(E_ALL);

// carica config
$configFile = __DIR__ . '/config.local.json';

if (!file_exists($configFile)) {
  fwrite(STDERR, "❌ Config mancante: config.local.json\n");
  exit(1);
}

Config::loadFromJson($configFile);

// init error handler (senza Twig)
Error::init(null);

Debug::log("🚀 Bootstrap CLI inizializzato", 'CLI');
