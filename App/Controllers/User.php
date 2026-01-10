<?php

namespace App\Controllers;

use App\Config;
use Core\Controller;
use Core\View\TwigManager;

if (!defined("CLI_MODE")) {
  defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}

class User extends Controller
{
  public function __construct(TwigManager $twigManager)
  {
    parent::__construct($twigManager);

    // Filtro Twig locale al controller
    // $this->twigManager->addFilter('noDate', fn($date) => $date ?: '---');
  }

  public function index(...$params): void
  {
    $this->addCss('/public/assets/css/user.css', "css-custom");
    $this->addJs('/public/assets/js/user.js', "js-main-custom");

    $this->render('User/index', [
      'title' => 'Pagina User',
      'message' => 'Benvenuto nella pagina User!',
      'params' => $params,
      // il menu è già globale in Twig
    ]);
  }
  public function ElencoUtenti(...$params)
  {
    var_export($params);
    $this->render('User/index', [
      'title' => 'Pagina User',
      'message' => 'Benvenuto nella pagina User!',
      'params' => $params,
      // il menu è già globale in Twig
    ]);
  }
}
