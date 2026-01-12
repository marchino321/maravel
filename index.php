<?php

declare(strict_types=1);

// Composer Autoload (vendor è dentro public_html)
require_once __DIR__ . '/vendor/autoload.php';


use App\Config;
use App\Debug;
use Core\Auth;
use Core\Router;
use Core\Plugin;
use Core\View\MenuManager;
use Core\View\TwigManager;
use Core\Classi\Menu;
use Core\Classi\TwigService;
use Core\Lang;
use Core\View\CoreAssets;
use Core\View\ThemeManager;

// -------------------------
// Config e bootstrap
// -------------------------

$configFile = __DIR__ . '/ConfigFiles/config.local.json';

if (!file_exists($configFile)) {
  require __DIR__ . '/install.php';
  exit;
}

Config::loadFromJson($configFile);
Debug::log("✅ Configurazione caricata da {$configFile}", 'CONFIG');

require_once __DIR__ . '/ConfigFiles/bootstrap.php';
Debug::log("Bootstrap inizializzato", 'BOOTSTRAP');





// -------------------------
// Controllo se la richiesta è API
// -------------------------
$uri   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$isApi = str_starts_with(trim($uri, '/'), 'api/');

$twigManager = null;
$menuManager = null;

// -------------------------
// Se NON è API → carico Menu e Twig
// -------------------------
if (!$isApi) {
  $userRoles = Auth::check() ? [Auth::role()] : [];
  if (!MenuManager::getInstance()) {



    $menuManager = new MenuManager($userRoles, []);
    MenuManager::setInstance($menuManager);
  } else {
    $menuManager = MenuManager::getInstance();
  }



  $menu        = new Menu($menuManager);
  $menu->menuAdmin(); // per admin
  CoreAssets::register();
  ThemeManager::boot();
  ThemeManager::loadFunctions();

  $twigManager = new TwigManager();
  $twig        = TwigService::init($menuManager);
  ThemeManager::setTwig($twig);
  $twigManager->setTwig($twig);

  // Variabili globali Twig

  $twigManager->addGlobal('NomeApp', Config::$site_name);
  $twigManager->addGlobal('Logo_App', Config::$Logo_App);
  $twigManager->addGlobal('link_footer', Config::$link_footer);
  $twigManager->addGlobal('label_link', Config::$label_link);
  $twigManager->addGlobal('design_footer', Config::$design_footer);
  $twigManager->addGlobal('debug', Config::$DEBUG_CONSOLE);
} else {
  Debug::log("🌐 Richiesta API → skip Twig/Menu", 'APP');
}

// -------------------------
// Router + Plugin
// -------------------------
$router = new Router($twigManager, $menuManager);

$pluginManager = Plugin::loadAll();

if (!$isApi) {
  // 🔌 QUI i plugin registrano lingue, path, menu, ecc.
  $pluginManager->registerRoutes($router, $twigManager, $menuManager);

  // $headerPlugin = new \App\Plugins\HeaderMenu\HeaderMenu();
  // $twig->addGlobal('headerMenu', $headerPlugin->getMenu());
  $twigManager->addGlobal('menu', $menuManager->renderForTwig());
}


$supported = Lang::available();
//dd($supported);
$lang = $_GET['lang'] ?? ($_SESSION['lang'] ?? null);
if (!$lang) {
  $lang = Lang::detectBrowserLang($supported);
}
if (!in_array($lang, $supported, true)) {
  $lang = 'it';
}
$_SESSION['lang'] = $lang;
// 🌍 ORA le lingue dei plugin esistono
Lang::set($lang);
if (!$isApi) {
  $twigManager->addGlobal('availableLangs', Lang::available());
  $twigManager->addGlobal('currentLang', Lang::current());
}


Debug::log("Lingue supporate adesso " . implode(', ', Lang::available()), 'LANG');
// -------------------------
// Dispatch della richiesta
// -------------------------
Debug::log("🚦 Dispatch URI: {$uri}", 'ROUTER');
$output = $router->dispatch($uri);

if ($output) {
  echo $output;
}

// -------------------------
// Fine caricamento
// -------------------------
\Core\EventManager::debugFiredEvents();
Debug::log("✅ Dispatch completato, stampo output", 'APP');
Debug::log("********** FINE CARICAMENTO APP **********", 'APP');

if (Config::$DEBUG_CONSOLE && !$isApi) {
  echo Debug::render();
}
