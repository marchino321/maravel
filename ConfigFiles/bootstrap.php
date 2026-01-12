<?php

declare(strict_types=1);

use App\Config;
use App\Debug;
use Core\Error;
use Core\View\ThemeManager;
use Core\View\TwigManager;
use Core\Security\SecurityDetector;
// -------------------------
// Impostazioni di sistema
// -------------------------
ini_set('session.cookie_lifetime', '31536000');
ini_set('session.gc_maxlifetime', '31536000');
session_start();
date_default_timezone_set('Europe/Rome');

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");




foreach ($_REQUEST as $key => $value) {
  if (is_string($value)) {
    SecurityDetector::analyze($value, "REQUEST:$key");
  }
}


// Autoload Composer caricato da index.php
//require_once __DIR__ . '/../vendor/autoload.php';
Debug::log("✅ Autoload Composer caricato", 'APP');

define('CLI_WEB_ALLOWED', true);

if (!defined(Config::$ABS_KEY)) {
  define(Config::$ABS_KEY, __DIR__ . '/');
}

// -------------------------
// Gestione errori
// -------------------------
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set("error_log", __DIR__ . "/php_errors.log");
error_reporting(E_ALL);

set_exception_handler([Error::class, 'handle']);
set_error_handler(
  fn($severity, $message, $file, $line) =>
  throw new \ErrorException($message, 0, $severity, $file, $line)
);
Debug::log("✅ Gestione errori inizializzata", 'APP');

// -------------------------
// Funzioni custom
// -------------------------
foreach (glob(__DIR__ . "/../Core/Funzioni/*.php") as $funzioneFile) {
  require_once $funzioneFile;
}
Debug::log("✅ Caricate funzioni custom", 'APP');

// -------------------------
// Configurazioni
// -------------------------
$configFile = __DIR__ . '/../ConfigFiles/config.local.json';
if (!file_exists($configFile)) {
  Debug::log("⚠️ File config mancante: $configFile", 'ERROR');
}

Config::runHooks();
Debug::log("✅ Hook configurazione eseguiti", 'CONFIG');

// -------------------------
// Debug iniziale
// -------------------------
Debug::log(str_repeat('*', 50), 'APP');
Debug::log("🚀 INIZIO CARICAMENTO APP", 'APP');
Debug::log(str_repeat('*', 50), 'APP');
