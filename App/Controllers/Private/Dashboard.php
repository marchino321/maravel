<?php

namespace App\Controllers\Private;

use App\Debug;
use Core\Classi\Flash;
use Core\Controller;
use Core\View\TwigManager;
use Core\View\MenuManager;
use Core\Components\DynamicTable;
use Core\Helpers\FormHelper;
use App\Config;

use Core\View\Page;

if (!defined("CLI_MODE")) {
  defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}
class Dashboard extends Controller
{

  //ck_857f1c59fa99a7e54fb606822ec4270dc12896d7 client_key
  //cs_e0dd441a0970058e3a6b897b604e3525d945876b client_secret
  public function Index(...$params): void
  {

    Flash::AddByKey('insert.ok');
    Page::setTitle('Dashboard');
    $ritorno =  [];
    echo $this->twigManager->getTwig()->render('Private/Dashboard/index.html', $ritorno);
  }
}
