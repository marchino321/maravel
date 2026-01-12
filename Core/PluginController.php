<?php

declare(strict_types=1);

namespace Core;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use App\Config;
use App\Debug;
use Core\Classi\Flash;

if (!defined("CLI_MODE")) {
  defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}
/**
 * Classe base per i controller dei plugin.
 *
 * Gestisce:
 *  - CSS/JS separati per plugin e controller principale
 *  - Risoluzione delle dipendenze JS
 *  - Logging dettagliato con Debug::log()
 *  - Twig loader limitato alla cartella del plugin
 */
abstract class PluginController
{
  protected Environment $twig;

  /** @var array<string,string> CSS del controller principale (id => url) */
  protected array $css = [];

  /** @var array<string,array{file:string,deps:array}> JS del controller principale */
  protected array $js = [];

  /** @var array<string,string> CSS aggiunti dal plugin (id => url) */
  protected array $pluginCss = [];

  /** @var array<string,array{file:string,deps:array}> JS aggiunti dal plugin */
  protected array $pluginJs = [];

  /**
   * Costruttore: inizializza Twig e logging
   *
   * @param string|null $pluginName Nome del plugin per individuare le Views
   */
  public function __construct(?string $pluginName = null)
  {
    $paths = [];

    // Se è specificato un plugin, carica le sue Views
    if ($pluginName) {
      $pluginViews = Config::$pluginDir . '/' . $pluginName . '/Views';
      if (is_dir($pluginViews)) {
        $paths[] = $pluginViews;
      }
    }

    // Fallback globale
    $paths[] = Config::$viewsDir;

    $loader = new FilesystemLoader($paths);
    $this->twig = new Environment($loader);

    Debug::log(str_repeat("=", 80), 'PLUGIN_CONTROLLER');
    Debug::log("Inizializzazione PluginController: " . static::class, 'PLUGIN_CONTROLLER');
    Debug::log("Percorsi Twig registrati: " . implode(', ', $paths), 'PLUGIN_CONTROLLER');
    Debug::log(str_repeat("=", 80), 'PLUGIN_CONTROLLER');
  }

  /**
   * Renderizza un template Twig
   *
   * @param string $template Nome del template (senza estensione .twig)
   * @param array<string,mixed> $params Parametri da passare alla view
   */
  protected function render(string $template, array $params = []): void
  {
    Debug::log("Rendering template plugin: {$template}.html", 'PLUGIN_CONTROLLER');

    // CSS unificati
    $allCss = $this->css + $this->pluginCss;
    $cssWithId = [];
    foreach ($allCss as $id => $url) {
      $cssWithId[] = ['id' => $id, 'url' => $url];
    }

    // JS unificati e ordinati con risoluzione dipendenze
    $allJs = $this->js + $this->pluginJs;
    $jsOrdered = $this->resolveJsDependencies($allJs);

    Debug::log("Rendering con " . count($cssWithId) . " CSS e " . count($jsOrdered) . " JS", 'PLUGIN_CONTROLLER');

    echo $this->twig->render($template . '.html', array_merge($params, [
      'css_files' => $cssWithId,
      'js_files'  => $jsOrdered
    ]));
  }

  /**
   * Aggiunge un CSS del plugin
   *
   * @param string $file Percorso del file CSS
   * @param string|null $id ID univoco (se null viene generato da md5)
   */
  protected function addCss(string $file, ?string $id = null): void
  {
    $id ??= md5($file);

    if (!isset($this->pluginCss[$id]) && !isset($this->css[$id])) {
      $this->pluginCss[$id] = $file;
    }

    if (Config::$DEBUG_CONSOLE) {
      $path = $_SERVER['DOCUMENT_ROOT'] . $file;
      if (!file_exists($path)) {
        Debug::log("⚠ CSS non trovato plugin [{$id}]: {$file}", 'ERROR');
      } else {
        Debug::log("➕ CSS aggiunto plugin [{$id}]: {$file}", 'PLUGIN_CONTROLLER');
      }
    }
  }

  /**
   * Aggiunge un JS del plugin con dipendenze opzionali
   *
   * @param string $file Percorso file JS
   * @param string|null $id ID univoco (se null viene generato da md5)
   * @param array<string> $deps Array di ID di dipendenze
   */
  protected function addJs(string $file, ?string $id = null, array $deps = []): void
  {
    $id ??= md5($file);

    if (!isset($this->pluginJs[$id]) && !isset($this->js[$id])) {
      $this->pluginJs[$id] = ['file' => $file, 'deps' => $deps];
    }

    if (Config::$DEBUG_CONSOLE) {
      $path = $_SERVER['DOCUMENT_ROOT'] . $file;
      if (!file_exists($path)) {
        Debug::log("⚠ JS non trovato plugin [{$id}]: {$file}, deps: " . implode(',', $deps), 'ERROR');
      } else {
        Debug::log("➕ JS aggiunto plugin [{$id}]: {$file}, deps: " . implode(',', $deps), 'PLUGIN_CONTROLLER');
      }
    }
  }

  /**
   * Risolve le dipendenze JS e restituisce l'elenco ordinato
   *
   * @param array<string,array{file:string,deps:array}>|null $jsArray
   * @return array<int,array{id:string,file:string}>
   */
  private function resolveJsDependencies(?array $jsArray = null): array
  {
    $jsArray ??= $this->js;
    $ordered = [];
    $visited = [];

    $visit = function (string $id) use (&$visit, &$ordered, &$visited, &$jsArray): void {
      if (isset($visited[$id])) {
        return;
      }
      $visited[$id] = true;

      if (!isset($jsArray[$id])) {
        return;
      }

      foreach ($jsArray[$id]['deps'] as $dep) {
        if (isset($jsArray[$dep])) {
          $visit($dep);
        }
      }

      if ($jsArray[$id]['file']) {
        $ordered[] = ['id' => $id, 'file' => $jsArray[$id]['file']];
      }
    };

    foreach (array_keys($jsArray) as $id) {
      $visit($id);
    }

    Debug::log(
      "📜 JS ordinati plugin: " . implode(', ', array_map(fn($e) => $e['id'], $ordered)),
      'PLUGIN_CONTROLLER'
    );

    return $ordered;
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
}
