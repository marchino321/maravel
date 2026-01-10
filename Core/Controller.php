<?php

declare(strict_types=1);

namespace Core;

use App\Config;
use Core\View\TwigManager;
use Core\View\MenuManager;
use Core\Classi\Chiamate;
use App\Debug;
use Core\Classi\Flash;

if (!defined("CLI_MODE")) {
  defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}
/** Vengo dalla versione nuova
 * Classe astratta Controller
 *
 * Base comune per:
 *  - Frontend
 *  - Area privata
 *  - API
 *
 * Gestisce:
 *  - Rendering Twig tramite TwigManager
 *  - Inclusione CSS/JS con gestione dipendenze
 *  - Accesso helper al DB tramite Chiamate
 *  - Logging centralizzato
 */
abstract class Controller
{
  /** @var TwigManager Gestore di Twig */
  protected TwigManager $twigManager;

  /** @var array<string,string> Lista file CSS del controller [id => url] */
  protected array $css = [];

  /** @var array<string,array{file:string,deps:array}> Lista file JS del controller [id => {file, deps}] */
  protected array $js = [];

  /** @var Chiamate Wrapper per query al database */
  protected Chiamate $c;

  /** @var MenuManager|null Gestore dei menu (shared) */
  protected ?MenuManager $menuManager = null;

  /** @var bool|null Esito accesso al menu corrente */
  protected ?bool $menuAccess = null;

  /** @var string|null ID menu derivato dall’URL */
  protected ?string $menuId = null;

  /**
   * Costruttore del Controller base
   *
   * @param TwigManager $twigManager Gestore Twig globale
   */
  public function __construct(TwigManager $twigManager, ?MenuManager $menuManager = null)
  {
    $this->twigManager = $twigManager;
    $this->c = new Chiamate();

    // Usa l’istanza condivisa se non viene iniettata
    $this->menuManager = $menuManager ?: MenuManager::getInstance();

    // Estrai ID menu dall’URL
    $this->menuId = $this->matchMenuId($_SERVER['REQUEST_URI'] ?? '', $this->menuManager->getAll());

    //dd($this->menuId);
    // Se ho sia manager che id → controllo accesso una volta sola qui
    if ($this->menuManager && $this->menuId !== '' && $this->menuId !== null) {
      $this->menuAccess = $this->menuManager->canAccessMenu($this->menuId);
      if (!$this->menuAccess) {
        http_response_code(403);
        echo $this->twigManager?->getTwig()->render('Error/Errors.html', [
          'title'     => 'Accesso Negato!',
          'messaggio' => 'Non hai accesso a questa area',
          'risposta'  => ""
        ]);
        exit;
      }
    } elseif (!$this->menuManager) {
      Debug::log("⚠️ MenuManager non disponibile (istanza condivisa mancante).", 'WARNING');
    }


    Debug::log("🚀 Inizializzazione Controller: " . static::class, 'CONTROLLER');
  }

  /**
   * Renderizza un template Twig
   *
   * @param string $template Nome del template (senza estensione .twig)
   * @param array $params Variabili da passare alla view
   */
  protected function render(string $template, array $params = []): void
  {
    // 🔹 Prepara CSS
    $cssWithId = [];
    foreach ($this->css as $id => $url) {
      $cssWithId[] = ['id' => $id, 'url' => $url];
    }

    // 🔹 Prepara JS ordinati
    $jsOrdered = $this->resolveJsDependencies();

    // 🔹 Merge parametri
    $params = array_merge($params, [
      'css_files' => $cssWithId,
      'js_files'  => $jsOrdered
    ]);

    Debug::log("🎨 Rendering template: {$template}.html", 'TEMPLATE');

    try {
      // 👉 carico e renderizzo così catturo anche SyntaxError
      $output = $this->twigManager->getTwig()
        ->load($template . '.html')
        ->render($params);

      echo $output;
    } catch (\Twig\Error\Error $e) {
      // errori Twig noti (Syntax, Runtime, Loader)
      $this->showTwigError($e, $template);
    } catch (\Throwable $e) {
      // errori generici
      $this->showTwigError($e, $template);
    }
  }

  /**
   * Mostra un pannello con dettagli errore Twig
   */
  private function showTwigError(\Throwable $e, string $template): void
  {
    Debug::log("❌ Twig error in $template: " . $e->getMessage(), 'ERROR');
    http_response_code(500);

    $isAdmin = Auth::check() && Auth::id() == Config::$ID_SUPER_ADMIN;

    if ($isAdmin) {
      echo "<div style='background:#1e1e1e;color:#f33;padding:20px;font-family:monospace;font-size:14px'>";
      echo "<h2>🐞 Errore Twig</h2>";
      echo "<p><b>Template:</b> " . htmlspecialchars($template) . ".html</p>";
      echo "<p><b>Tipo:</b> " . get_class($e) . "</p>";
      echo "<p><b>Messaggio:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
      echo "<p><b>File:</b> " . htmlspecialchars($e->getFile()) . "</p>";
      echo "<p><b>Linea:</b> " . $e->getLine() . "</p>";
      echo "<pre style='color:#ccc;font-size:12px;overflow:auto;max-height:300px'>" .
        htmlspecialchars($e->getTraceAsString()) .
        "</pre>";
      echo "</div>";
    } else {
      echo "<h2>⚠️ Errore di rendering</h2>";
      echo "<p>Si è verificato un problema durante il caricamento della pagina.</p>";
    }
  }

  /**
   * Aggiunge un file CSS al controller
   *
   * @param string $file Percorso relativo del file CSS
   * @param string|null $id Identificatore univoco (se null viene generato un md5)
   */
  protected function addCss(string $file, ?string $id = null): void
  {
    $id ??= md5($file);
    $this->css[$id] = $file;

    $path = $_SERVER['DOCUMENT_ROOT'] . $file;
    if (!file_exists($path)) {
      Debug::log("⚠️ CSS non trovato: $file (id=$id)", 'ERROR');
    } else {
      Debug::log("➕ CSS aggiunto: $file (id=$id)", 'CONTROLLER');
    }
  }

  /**
   * Aggiunge un file JS al controller
   *
   * @param string $file Percorso relativo del file JS
   * @param string|null $id Identificatore univoco (se null viene generato un md5)
   * @param array $deps Lista di ID di script dai quali dipende
   */
  protected function addJs(string $file, ?string $id = null, array $deps = []): void
  {
    $id ??= md5($file);
    $this->js[$id] = ['file' => $file, 'deps' => $deps];

    $path = $_SERVER['DOCUMENT_ROOT'] . $file;
    if (!file_exists($path)) {
      Debug::log("⚠️ JS non trovato: $file (id=$id, deps=[" . implode(',', $deps) . "])", 'ERROR');
    } else {
      Debug::log("➕ JS aggiunto: $file (id=$id, deps=[" . implode(',', $deps) . "])", 'CONTROLLER');
    }
  }

  /**
   * Risolve e ordina i file JS rispettando le dipendenze
   *
   * @return array<array{id:string,file:string}> Lista ordinata di file JS
   */
  private function resolveJsDependencies(): array
  {
    $ordered = [];
    $visited = [];

    $visit = function (string $id) use (&$visit, &$ordered, &$visited): void {
      if (isset($visited[$id])) {
        return;
      }
      $visited[$id] = true;

      if (!isset($this->js[$id])) {
        return;
      }

      foreach ($this->js[$id]['deps'] as $dep) {
        if (isset($this->js[$dep])) {
          $visit($dep);
        }
      }

      if ($this->js[$id]['file']) {
        $ordered[] = ['id' => $id, 'file' => $this->js[$id]['file']];
      }
    };

    foreach (array_keys($this->js) as $id) {
      $visit($id);
    }

    Debug::log("📜 JS ordinati: " . implode(', ', array_column($ordered, 'id')), 'CONTROLLER');

    return $ordered;
  }
  protected function isAjax(): bool
  {
    return (
      !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
      strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    );
  }

  protected function jsonResponse(bool $success, array $data = []): void
  {
    header('Content-Type: application/json; charset=utf-8');

    $logs = Debug::renderAjaxLogs("AJAX"); // raccoglie i log già accumulati
    // recupera i messaggi flash attivi
    $flashMessages = Flash::GetMex();
    $response = [
      'success' => $success,
      'data'    => $data,
      'flash'   => $flashMessages,
      'logs'    => $logs,
    ];

    // 🔀 Se nei data c’è redirect → lo metto anche a livello root
    if (isset($data['redirect'])) {
      $response['redirect'] = $data['redirect'];
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
  }

  /**
   * Estrae l'ID menu dall'URL
   * es: /collaboratori/aggiungi -> collaboratori_aggiungi
   */
  private function matchMenuId(string $currentUri, array $menu): ?string
  {
    // 1. Ripulisci l’URI (rimuovi querystring e slash finale)
    $uri = parse_url($currentUri, PHP_URL_PATH);
    $uri = rtrim($uri, '/');

    foreach ($menu as $id => $item) {
      if (!isset($item['url'])) {
        continue;
      }

      $menuUrl = rtrim($item['url'], '/');

      // 2. Se l’URI inizia con l’url del menu → match
      if (str_starts_with($uri, $menuUrl)) {
        return $id; // es: "collaboratori_aggiungi"
      }
    }

    return null;
  }
}
