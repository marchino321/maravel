<?php

namespace Core\View;

use App\Debug;

/**
 * Classe MenuManager
 * Gestisce menu dinamici con supporto a:
 *  - Menu padre/figlio
 *  - Ruoli utente
 *  - Menu di default caricabili e sovrascrivibili
 *  - Log di debug per ogni operazione
 *  - Icone opzionali per ogni voce
 */
class MenuManager
{
  private array $menu = [];         // Menu completo con voci e figli
  private array $userRoles = [];    // Ruoli dell'utente corrente
  private array $defaultMenu = [];  // Menu di default caricabile
  private static ?self $instance = null;

  /**
   * Costruttore
   * @param array $userRoles Ruoli dell'utente corrente
   * @param array $defaultMenu Menu di default (può essere sovrascritto dai plugin)
   */
  public function __construct(array $userRoles = [], array $defaultMenu = [])
  {
    $this->userRoles = $userRoles;
    $this->defaultMenu = $defaultMenu;

    // Carica menu di default se presente
    if (!empty($this->defaultMenu)) {
      $this->loadDefaults($this->defaultMenu);
    }

    Debug::log("✏️ MenuManager inizializzato con ruoli: " . implode(',', $userRoles), 'MENU');
  }

  /**
   * Aggiunge o sovrascrive una voce di menu
   * @param string $id Identificativo univoco
   * @param string $label Etichetta visibile
   * @param string $url URL o route della voce
   * @param array $roles Ruoli abilitati a vedere questa voce
   * @param int $order Ordine di visualizzazione
   * @param string|null $parent ID del menu padre se voce secondaria
   * @param string|null $icon Icona opzionale (classe o path)
   */
  public static function setInstance(self $instance): void
  {
    self::$instance = $instance;
  }

  public static function getInstance(): ?self
  {
    return self::$instance;
  }
  public function setMenuItem(
    string $id,
    string $label,
    string $url,
    array $roles = [],
    int $order = 50,
    ?string $parent = null,
    ?string $icon = null
  ): void {
    $this->menu[$id] = [
      'id' => $id,
      'label' => $label,
      'url' => $url,
      'roles' => $roles,
      'order' => $order,
      'parent' => $parent,
      'children' => [],
      'icon' => $icon // Nuovo campo icona
    ];

    Debug::log("✏️ Voce menu impostata: $label ($id)" . ($icon ? " con icona: $icon" : ""), 'MENU');
  }

  /**
   * Aggiunge una voce di menu solo se non esiste già
   */
  public function addMenuItem(
    string $id,
    string $label,
    string $url,
    array $roles = [],
    int $order = 50,
    ?string $parent = null,
    ?string $icon = null
  ): void {

    if (!isset($this->menu[$id])) {
      $this->setMenuItem($id, $label, $url, $roles, $order, $parent, $icon);
    } else {
      Debug::log("✏️ Voce menu $id già esistente, non aggiunta", 'MENU');
    }
  }

  /**
   * Rimuove una voce di menu
   */
  public function removeMenuItem(string $id): void
  {
    if (isset($this->menu[$id])) {
      unset($this->menu[$id]);
      Debug::log("✏️ Voce menu rimossa: $id", 'MENU');
    }
  }

  /**
   * Carica menu di default
   * Plugins o configurazioni possono sovrascrivere voci esistenti
   * @param array $defaults Array di voci di default
   */
  public function loadDefaults(array $defaults): void
  {
    foreach ($defaults as $item) {
      $id = $item['id'] ?? null;
      if (!$id) continue;

      $this->setMenuItem(
        $id,
        $item['label'] ?? 'Unnamed',
        $item['url'] ?? '#',
        $item['roles'] ?? [],
        $item['order'] ?? 50,
        $item['parent'] ?? null,
        $item['icon'] ?? null // Supporto icona
      );
    }

    Debug::log("✏️ Menu di default caricati: " . count($defaults), 'MENU');
  }

  /**
   * Costruisce l'albero padre-figlio e filtra per ruoli utente
   * @return array Menu pronto per Twig
   */
  public function build(): array
  {
    // Reset dei figli
    foreach ($this->menu as &$item) {
      $item['children'] = [];
    }
    unset($item);

    $tree = [];
    foreach ($this->menu as $id => $item) {
      // Controlla ruoli
      if (!empty($item['roles']) && empty(array_intersect($item['roles'], $this->userRoles))) {
        continue;
      }

      if ($item['parent'] && isset($this->menu[$item['parent']])) {
        $this->menu[$item['parent']]['children'][] = &$this->menu[$id];
      } else {
        $tree[] = &$this->menu[$id];
      }
    }

    // Ordina ricorsivamente
    $sortFn = function (&$items) use (&$sortFn) {
      usort($items, fn($a, $b) => $a['order'] <=> $b['order']);
      foreach ($items as &$child) {
        if (!empty($child['children'])) $sortFn($child['children']);
      }
    };

    $sortFn($tree);
    return $tree;
  }

  /**
   * Restituisce il menu pronto per Twig
   */
  public function renderForTwig(): array
  {
    return $this->build();
  }

  /**
   * Recupera tutte le voci di menu (non filtrate)
   */
  public function getAll(): array
  {
    return $this->menu;
  }


  /**
   * Controlla se l'utente corrente ha almeno uno dei ruoli richiesti
   */
  public function hasAccess(array $requiredRoles): bool
  {
    if (empty($requiredRoles)) {
      return true; // accesso libero se non ci sono restrizioni
    }
    return (bool) array_intersect($this->userRoles, $requiredRoles);
  }

  /**
   * Controlla se l'utente può accedere a una voce di menu specifica
   */
  public function canAccessMenu(string $menuKey): bool
  {
    $item = $this->menu[$menuKey] ?? null;
    if (!$item) {
      return false;
    }
    return $this->hasAccess($item['roles'] ?? []);
  }
}
