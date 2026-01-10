<?php

namespace Core\Classi;

use App\Config;
use App\Debug;
use Core\Auth;
use Core\View\MenuManager;

if (!defined("CLI_MODE")) {
  defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}
/**
 * Classe Menu compatibile Twig
 * Genera dinamicamente il menu laterale per Admin e SuperAdmin
 */
class Menu
{
  private MenuManager $menuManager;

  public function __construct(MenuManager $menuManager)
  {
    $this->menuManager = $menuManager;
    Debug::log("MenuManager inizializzato in Menu class", 'MENU');
  }

  /**
   * Menu SuperAdmin rapido con icone
   */
  public function menuSuperAdmin(): void
  {
    $superAdminItems = [
      ['id' => 'super_stats', 'label' => '', 'url' => '/SuperAdmin/Index/SuperUser', 'icon' => 'fe-activity', 'title' => 'Statistiche Iniziali SuperAdmin'],
      ['id' => 'super_migration', 'label' => '', 'url' => '/SuperAdmin/Migration/SuperUser', 'icon' => 'fe-database', 'title' => 'Migrazioni'],
      ['id' => 'super_coding', 'label' => '', 'url' => '/SuperAdmin/Coding/SuperUser', 'icon' => 'fe-code', 'title' => 'Documentazione Codice'],
      ['id' => 'example_form', 'label' => '', 'url' => '/home/esempioform', 'icon' => 'fe-layout', 'title' => 'Esempio di inserimento'],
      ['id' => 'export_db_true', 'label' => '', 'url' => '/installa/ExportDB/SuperUser?db=true', 'icon' => 'fe-download-cloud', 'title' => 'Esporta Progetto'],
      ['id' => 'export_db', 'label' => '', 'url' => '/installa/ExportDB/SuperUser', 'icon' => 'fe-download', 'title' => 'Crea nuova migrazione'],
      ['id' => 'date_pattern', 'label' => '', 'url' => '/SuperAdmin/DatePattern/SuperUser', 'icon' => 'fe-calendar', 'title' => 'Pattern Date'],
    ];

    foreach ($superAdminItems as $item) {
      $this->menuManager->addMenuItem(
        $item['id'],
        $item['label'],
        $item['url'],
        [], // ruoli SuperAdmin
        10,
        null
      );
    }

    Debug::log("Menu SuperAdmin generato", 'MENU');
  }

  /**
   * Menu Admin principale con figli
   */
  public function menuAdmin(): void
  {
    $role = [];
    if (Auth::check()) {
      $role[] = Auth::role();
    }


    // Dashboard
    $this->menuManager->addMenuItem(
      'dashboard',
      'Dashboard',
      '/private/dashboard',
      [],
      1,
      null,
      'mdi mdi-view-dashboard'
    );

    // Collaboratori
    $this->menuManager->addMenuItem(
      'collaboratori',
      'Collaboratori',
      '#',
      ['Admin'],
      100,
      null,
      'mdi mdi-account-group'
    );
    $this->menuManager->addMenuItem(
      'collaboratori_elenco',
      'Elenco',
      '/private/collaboratori/elenco-collaboratori',
      ['Admin'],
      1,
      'collaboratori',
      'mdi mdi-account-box-multiple'
    );
    $this->menuManager->addMenuItem(
      'collaboratori_aggiungi',
      'Aggiungi',
      '/private/collaboratori/aggiungi-modifica-collaboratore',
      ['Admin'],
      2,
      'collaboratori',
      'mdi mdi-account-plus'
    );




    Debug::log("Menu Admin generato con tutte le voci e figli", 'MENU');
  }
}
