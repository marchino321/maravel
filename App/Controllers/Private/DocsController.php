<?php

namespace App\Controllers\Private;

use App\Debug;
use Core\Classi\Flash;
use Core\Controller;
use Core\View\TwigManager;
use Core\View\MenuManager;
use App\Config;



if (!defined("CLI_MODE")) {
  defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}

class DocsController extends Controller
{
  public function Index(): void
  {
    echo $this->twigManager->getTwig()->render('Docs/Index.html', [
      'Titolo' => 'Documentazione'
    ]);
  }

  public function QueryBuilder(): void
  {
    echo $this->twigManager->getTwig()->render('Docs/QueryBuilder.html', [
      'Titolo' => '🗄 Query Builder'
    ]);
  }
  public function Ajax(): void
  {
    echo $this->twigManager->getTwig()->render('Docs/ajax.html', [
      'Titolo' => '⚡ AJAX'
    ]);
  }
  public function Api(): void
  {
    echo $this->twigManager->getTwig()->render('Docs/api.html', [
      'Titolo' => '🔐 API'
    ]);
  }
}
