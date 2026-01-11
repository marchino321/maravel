<?php

namespace Core\Classi;

use App\Config;
use App\Debug;
use Core\Auth;
use Core\View\MenuManager;

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
      'menu.dashboard',
      '/private/dashboard',
      [],
      1,
      null,
      'mdi mdi-view-dashboard'
    );

    // Collaboratori (parent)
    $this->menuManager->addMenuItem(
      'collaboratori',
      'menu.collaboratori',
      '#',
      ['Admin'],
      100,
      null,
      'mdi mdi-account-group'
    );

    $this->menuManager->addMenuItem(
      'collaboratori_elenco',
      'menu.collaboratori.elenco',
      '/private/collaboratori/elenco-collaboratori',
      ['Admin'],
      1,
      'collaboratori',
      'mdi mdi-account-box-multiple'
    );

    $this->menuManager->addMenuItem(
      'collaboratori_aggiungi',
      'menu.collaboratori.aggiungi',
      '/private/collaboratori/aggiungi-modifica-collaboratore',
      ['Admin'],
      2,
      'collaboratori',
      'mdi mdi-account-plus'
    );




    Debug::log("Menu Admin generato con tutte le voci e figli", 'MENU');
  }
}
