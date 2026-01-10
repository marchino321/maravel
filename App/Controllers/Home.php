<?php

namespace App\Controllers;

use App\Config;
use Core\Controller;
use Core\View\TwigManager;
use Core\View\MenuManager;

if (!defined("CLI_MODE")) {
  defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}

class Home extends Controller
{

  public function Index(...$params): void
  {
    echo $this->twigManager->getTwig()->render('Public/landing-page.html', [
      'Titolo' => "Home"
    ]);
  }
}
