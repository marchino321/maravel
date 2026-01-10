<?php

declare(strict_types=1);

use App\Config;
use App\Debug;
use Core\Error;
use Core\View\TwigManager;

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
} else {
  Config::loadFromJson($configFile);
  Debug::log("✅ Configurazione caricata da: $configFile", 'CONFIG');
}

Config::runHooks();
Debug::log("✅ Hook configurazione eseguiti", 'CONFIG');

// -------------------------
// Debug iniziale
// -------------------------
Debug::log(str_repeat('*', 50), 'APP');
Debug::log("🚀 INIZIO CARICAMENTO APP", 'APP');
Debug::log(str_repeat('*', 50), 'APP');

// -------------------------
// Funzione per assets globali
// -------------------------
function bootstrapAssets(TwigManager $twigManager): void
{
  Debug::log("🔧 Iniezione assets globali", 'ASSETS');

  // CSS globali
  $twigManager->addCssFile('/App/public/css/bootstrap-dark.min.css', 'bs-dark-css');
  $twigManager->addCssFile('/App/public/css/app-dark.min.css', 'app-default-stylesheet', ['bs-dark-css']);
  $twigManager->addCssFile('https://cdn.jsdelivr.net/npm/@sweetalert2/theme-dark@5/dark.css', 'sweetalert2-dark-css');

  $twigManager->addCssFile('/App/public/libs/toastr/build/toastr.min.css', 'toastr-css');
  $twigManager->addCssFile('/App/public/libs/select2/css/select2.min.css', 'select2-css');

  $twigManager->addCssFile('/App/public/css/icons.min.css', 'icons-css');
  $twigManager->addCssFile('https://cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.css', 'bs-time-picker-css');

  $twigManager->addCssFile('/App/public/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css', 'bs-data-table-css');

  $twigManager->addCssFile('/App/public/libs/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css', 'bs-button-table-css');
  $twigManager->addCssFile('/App/public/libs/datatables.net-select-bs5/css/select.bootstrap5.min.css', 'bs-select-table-css');

  $twigManager->addCssFile('/App/public/libs/bootstrap-datepicker/css/bootstrap-datepicker.min.css', 'bs-data-picker-css');
  $twigManager->addCssFile('https://cdn.jsdelivr.net/npm/pretty-checkbox@3.0/dist/pretty-checkbox.min.css', 'pretty-checkbox-css');

  $twigManager->addCssFile('/App/public/css/myStyle.css', 'my-css');
  $twigManager->addJsHeadFile('/App/public/js/vendor.js', 'vendor-js', ['jQuery']);
  $twigManager->addJsHeadFile('https://code.jquery.com/jquery-3.7.1.min.js', 'jQuery');

  $twigManager->addJsHeadFile('/App/public/libs/toastr/build/toastr.min.js', 'toastr-js', ['vendor-js']);
  $twigManager->addJsHeadFile('/App/public/js/Classes/AjaxHelper.js', 'ajax-helper-js', ['jQuery', 'toastr-js']);

  // JS globali

  $twigManager->addJsFile('/App/public/libs/tippy.js/tippy.all.min.js', 'tippy-js');
  $twigManager->addJsFile('/App/public/LibCustom/TableCustom/dataTableJquery.js', 'table-custom-js', ['vendor-js']);
  $twigManager->addJsFile('/App/public/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js', 'table-bs-jQuery-js', ['table-custom-js']);
  $twigManager->addJsFile('/App/public/js/Funzioni/Soldi.js', 'soldi-custom-js', ['vendor-js']);
  $twigManager->addJsFile('/App/public/js/app.js', 'app-js', ['vendor-js', 'soldi-custom-js']);
  $twigManager->addJsFile('https://cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.js', 'time-picker-js', ['vendor-js']);
  $twigManager->addJsFile('/App/public/libs/select2/js/select2.min.js', 'select2-js');
  $twigManager->addJsFile('/App/public/libs/bootstrap-datepicker/js/bootstrap-datepicker.min.js', 'ds-data-picker-js');
  $twigManager->addJsFile('https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js', 'sweetalert2-js', ['vendor-js']);
  $twigManager->addJsFile('/App/public/js/Funzioni/StandardFunction.js', 'standard-fn-js', ['toastr-js', 'app-js']);




  Debug::log("➕ CSS/JS globali caricati", 'ASSETS');
}
