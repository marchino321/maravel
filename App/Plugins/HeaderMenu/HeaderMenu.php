<?php

declare(strict_types=1);

namespace App\Plugins\HeaderMenu;

use Core\PluginController;
use App\Debug;
use Core\Auth;
use Core\Router;
use Core\View\TwigManager;
use Core\View\MenuManager;
use Core\View\ThemeManager;

/**
 * Plugin HeaderMenu
 *
 * RESPONSABILITÀ:
 * - Gestisce SOLO il menu NAVBAR (header)
 * - NON interagisce con MenuManager (sidebar)
 * - Espone il menu a Twig come variabile globale: headerMenu
 * - Viene istanziato UNA SOLA VOLTA (singleton plugin)
 */
class HeaderMenu extends PluginController
{
  /** Istanza unica del plugin */
  private static ?self $instance = null;

  /** Manager menu header */
  private HeaderMenuManager $headerMenu;

  /**
   * Costruttore
   * ⚠️ NON va mai chiamato manualmente
   */
  public function __construct()
  {
    // 🔒 Blocco istanziazioni multiple
    if (self::$instance !== null) {
      Debug::log('⚠️ HeaderMenu già istanziato, skip costruttore', 'HEADER-MENU');
      return;
    }

    self::$instance = $this;

    parent::__construct();

    // 🔐 Ruoli utente
    $userRoles = $_SESSION['ruoli'] ?? [];
    if (Auth::checkSuperAdmin()) {
      $userRoles = ['SuperAdmin'];
    }

    // 🧭 Definizione menu HEADER (navbar)
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
        "id"    => "plugins",
        "label" => "",
        "url"   => "/private/super-admin/plugins",
        "roles" => ['SuperAdmin'],
        "order" => 6,
        "icon"  => "fas fa-plug"
      ],

      [
        "id"    => "themes",
        "label" => "",
        "url"   => "/private/super-admin/themes",
        "roles" => ['SuperAdmin'],
        "order" => 7,
        "icon"  => "fas fa-palette"
      ],

      [
        "id"    => "snippets",
        "label" => "",
        "url"   => "/private/docs-controller/index",
        "roles" => ['SuperAdmin'],
        "order" => 8,
        "icon"  => "fas fa-code"
      ],

    ]);

    Debug::log('✅ Plugin HeaderMenu inizializzato correttamente', 'HEADER-MENU');
  }

  /**
   * Accesso singleton
   */
  public static function getInstance(): ?self
  {
    return self::$instance;
  }

  /**
   * Registrazione plugin
   * Qui avviene l’esposizione a Twig
   */
  public function register(
    Router $router,
    TwigManager $twigManager,
    ?MenuManager $menuManager = null
  ): void {

    // 🧩 Espone il menu HEADER a Twig
    $twigManager->addGlobal('headerMenu', $this->getMenu());

    // 🧱 Placeholder UI globale (una sola volta)
    ThemeManager::addOnce('body.after', '<div id="toast-root"></div>');

    Debug::log('🧩 HeaderMenu registrato e globale Twig aggiunta', 'HEADER-MENU');
  }

  /**
   * Menu pronto per Twig
   */
  public function getMenu(): array
  {
    return $this->headerMenu->renderForTwig();
  }
}
