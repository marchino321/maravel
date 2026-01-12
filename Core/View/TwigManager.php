<?php

namespace Core\View;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;
use App\Debug;
use App\Config;
use Core\SafeTwigEnvironment;

class TwigManager extends AbstractExtension
{
  private array $globals = [];
  private array $filters = [];
  private array $functions = [];
  private FilesystemLoader $loader;
  private Environment $twig;

  public function __construct(?string $jsonConfig = null)
  {
    // Carica le Views core + plugin
    $viewsPaths = [Config::$viewsDir];
    $pluginViews = glob(Config::$pluginDir . '/*/Views');
    $viewsPaths = array_merge($viewsPaths, $pluginViews);




    $this->loader = new FilesystemLoader($viewsPaths);

    $this->twig = new SafeTwigEnvironment($this->loader, [
      'debug'       => true,
      'auto_reload' => true,
      'cache'       => false,
      'strict_variables' => true,
    ]);


    // $loader = new FilesystemLoader($viewsPaths);
    // $this->twig = new Environment($loader, [
    //     'debug'       => true,
    //     'auto_reload' => true,
    //     'cache'       => false,
    //     'strict_variables' => true,
    // ]);

    $this->twig->addExtension($this);
    $this->twig->addExtension(new \Twig\Extension\DebugExtension());

    // 🔹 inizializzo SEMPRE i globali
    $this->globals = [
      'css_files'     => [],
      'inline_css'    => [],
      'js_files'      => [],
      'js_head_files' => [],
      'inline_js'     => [],
    ];

    // 🔹 Sincronizzo subito con Twig
    $this->syncGlobalsToTwig();

    // Filtro custom
    $this->addFilter('NumFormat', fn($n, $d = 2) => number_format($n, $d, ',', '.'));

    Debug::log("✏️ Twig Environment inizializzato con Views: " . implode(', ', $viewsPaths), 'VIEW');
  }
  /**
   * Registra un path Twig con namespace (per plugin / temi)
   * Uso: @ApiInspector/index.html
   */
  public function addPath(string $path, string $namespace): void
  {
    if (!is_dir($path)) {
      Debug::log("⚠️ Twig path non valido: {$path}", 'VIEW');
      return;
    }

    $this->loader->addPath($path, $namespace);

    Debug::log("📁 Twig path aggiunto: {$path} come @{$namespace}", 'VIEW');
  }
  public function setTwig(Environment $twig): void
  {
    $this->twig = $twig;
    $this->syncGlobalsToTwig();
    Debug::log("✏️ Twig Environment aggiornato tramite setTwig()", 'VIEW');
  }

  public function getTwig(): \Twig\Environment
  {
    return $this->twig;
  }

  public function addGlobal(string $name, mixed $value): void
  {
    $this->globals[$name] = $value;
    if (isset($this->twig)) {
      $this->twig->addGlobal($name, $value);
    }
    Debug::log("✏️ Variabile globale aggiunta: $name", 'VIEW');
  }

  private function syncGlobalsToTwig(): void
  {
    if (!isset($this->twig)) return;
    foreach ($this->globals as $key => $val) {
      $this->twig->addGlobal($key, $val);
    }
    Debug::log("🔄 Globali sincronizzati con Twig Environment", 'VIEW');
  }

  public function addFilter(string $name, callable $callback): void
  {
    if (!isset($this->filters[$name])) {
      $filter = new TwigFilter($name, $callback);
      $this->filters[$name] = $filter;
      $this->twig->addFilter($filter);
      Debug::log("✏️ Filtro Twig aggiunto: $name", 'VIEW');
    }
  }

  public function addFunction(string|TwigFunction $name, ?callable $callback = null): void
  {
    if ($name instanceof TwigFunction) {
      $function = $name;
      $name = $function->getName();
    } else {
      $function = new TwigFunction($name, $callback);
    }

    if (!isset($this->functions[$name])) {
      $this->functions[$name] = $function;
      $this->twig->addFunction($function);
      Debug::log("✏️ Funzione Twig aggiunta: $name", 'VIEW');
    }
  }

  public function addCssFile(string $url, string $id, array $deps = []): void
  {
    $cssFiles = $this->globals['css_files'] ?? [];

    foreach ($cssFiles as $css) {
      if ($css['id'] === $id) {
        Debug::log("⚠️ CSS già presente: $id", 'VIEW');
        return;
      }
    }

    $cssFiles[] = ['url' => $url, 'id' => $id, 'deps' => $deps];
    $this->globals['css_files'] = $cssFiles;
    $this->twig->addGlobal('css_files', $cssFiles);
    Debug::log("🎨 CSS aggiunto: $url con ID $id", 'VIEW');
  }

  private function resolveCssDependencies(array $cssFiles = []): array
  {
    if (empty($cssFiles)) return [];
    $ordered = [];
    $visited = [];

    $visit = function ($id) use (&$visit, &$ordered, &$visited, $cssFiles) {
      if (isset($visited[$id])) return;
      $visited[$id] = true;

      $css = array_filter($cssFiles, fn($c) => $c['id'] === $id);
      if (!$css) return;

      $css = array_values($css)[0];

      foreach ($css['deps'] as $dep) {
        $visit($dep);
      }

      $ordered[] = $css;
    };

    foreach ($cssFiles as $css) {
      $visit($css['id']);
    }

    return $ordered;
  }

  public function addJsFile(string $file, string $id, array $deps = []): void
  {
    $jsFiles = $this->globals['js_files'] ?? [];

    foreach ($jsFiles as $js) {
      if ($js['id'] === $id) {
        Debug::log("⚠️ JS già presente: $id", 'VIEW');
        return;
      }
    }

    $jsFiles[] = ['file' => $file, 'id' => $id, 'deps' => $deps];
    $this->globals['js_files'] = $jsFiles;
    $this->twig->addGlobal('js_files', $jsFiles);
    Debug::log("🟢 JS aggiunto: $file con ID $id", 'VIEW');
  }

  private function resolveJsDependencies(array $jsFiles = []): array
  {
    if (empty($jsFiles)) return [];
    $ordered = [];
    $visited = [];

    $visit = function ($id) use (&$visit, &$ordered, &$visited, $jsFiles) {
      if (isset($visited[$id])) return;
      $visited[$id] = true;

      $js = array_filter($jsFiles, fn($j) => $j['id'] === $id);
      if (!$js) return;

      $js = array_values($js)[0];

      foreach ($js['deps'] as $dep) {
        $visit($dep);
      }

      $ordered[] = $js;
    };

    foreach ($jsFiles as $js) {
      $visit($js['id']);
    }

    return $ordered;
  }

  public function addJsHeadFile(string $file, string $id, array $deps = []): void
  {
    $jsHeadFiles = $this->globals['js_head_files'] ?? [];

    foreach ($jsHeadFiles as $js) {
      if ($js['id'] === $id) {
        Debug::log("⚠️ JS HEAD già presente: $id", 'VIEW');
        return;
      }
    }

    $jsHeadFiles[] = ['file' => $file, 'id' => $id, 'deps' => $deps];
    $this->globals['js_head_files'] = $jsHeadFiles;
    $this->twig->addGlobal('js_head_files', $jsHeadFiles);
    Debug::log("🟢 JS HEAD aggiunto: $file con ID $id", 'VIEW');
  }
  /**
   * Render sicuro con gestione errori Twig
   */
}
