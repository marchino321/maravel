<?php

namespace App\Plugins\HeaderMenu;

use Core\PluginController;
use App\Debug;
use Core\Auth;

class HeaderMenu extends PluginController
{
  private HeaderMenuManager $headerMenu;

  public function __construct()
  {
    parent::__construct();

    // ruoli utente da sessione
    $userRoles = $_SESSION['ruoli'] ?? [];
    if (Auth::checkSuperAdmin()) {
      $userRoles = ['SuperAdmin'];
    }
    $this->headerMenu = new HeaderMenuManager($userRoles, [

      [
        "id"    => "home",
        "label" => "",
        "url"   => "/private/super-admin",
        "roles" => ['SuperAdmin'],
        "order" => 1,
        "icon"  => "fas fa-home"
      ],

      [
        "id"    => "migrazioni",
        "label" => "",
        "url"   => "/private/super-admin/migrazioni-database",
        "roles" => ['SuperAdmin'],
        "order" => 2,
        "icon"  => "fas fa-database"
      ],

      [
        "id"    => "documentazione",
        "label" => "",
        "url"   => "/private/super-admin/documentazione",
        "roles" => ['SuperAdmin'],
        "order" => 3,
        "icon"  => "fas fa-book-open"
      ],

      [
        "id"    => "export",
        "label" => "",
        "url"   => "/private/super-admin/esporta-progetto",
        "roles" => ['SuperAdmin'],
        "order" => 4,
        "icon"  => "fas fa-file-export"
      ],

      [
        "id"    => "update",
        "label" => "",
        "url"   => "/private/super-admin/aggiornamenti",
        "roles" => ['SuperAdmin'],
        "order" => 5,
        "icon"  => "fas fa-cloud-download-alt"
      ],

      [
        "id"    => "snippets",
        "label" => "",
        "url"   => "/private/docs-controller/index",
        "roles" => ['SuperAdmin'],
        "order" => 6,
        "icon"  => "fas fa-code"
      ],

    ]);

    Debug::log("🔌 Plugin HeaderMenu caricato", "HEADER-MENU");
  }

  public function getMenu(): array
  {
    return $this->headerMenu->renderForTwig();
  }
}
