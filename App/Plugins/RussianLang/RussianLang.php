<?php

namespace App\Plugins\RussianLang;

use Core\PluginController;
use Core\Router;
use Core\View\TwigManager;
use Core\View\MenuManager;
use Core\EventManager;
use App\Debug;
use App\Config;
use Core\Auth;
use Core\Classi\Flash;
use Core\Lang;

class RussianLang  extends PluginController
{
  private TwigManager $twigManager;

  public function register(Router $router, TwigManager $twigManager, ?MenuManager $menuManager = null): void
  {

    // 1️⃣ registra la lingua globale
    Lang::registerLanguage('ru');

    // 2️⃣ registra il file di traduzione globale
    Lang::registerPluginLangPath(__DIR__ . '/Lang');
    Debug::log("Path lingue nuove " . '"' . __DIR__ . '/Lang' . '"', 'PLUGIN');
  }
}
