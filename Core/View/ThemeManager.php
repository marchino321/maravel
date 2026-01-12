<?php

namespace Core\View;

use App\Config;
use App\Debug;

final class ThemeManager
{
  private static array $hooks = [
    'head.before'   => [],
    'head.after'    => [],
    'body.before'   => [],
    'body.after'    => [],
    'footer.before' => [],
    'footer.after'  => [],
  ];
  private static array $assets = [
    'css'       => [],
    'js_head'   => [],
    'js_footer' => [],
    'inline_css' => [],   // 👈
    'inline_js' => [],   // 👈
  ];
  private static array $registered = [];
  private static array $once = [];
  public static string $theme;
  public static string $basePath;
  protected static array $inlineCss = [];
  private static ?\Twig\Environment $twig = null;

  public static function boot(): void
  {
    self::$theme = Config::$THEME ?? 'default';
    self::$basePath = Config::$baseDir . '/App/Theme/' . self::$theme;

    if (!is_dir(self::$basePath)) {
      throw new \RuntimeException('Tema non trovato: ' . self::$basePath);
    }

    Debug::log('Tema attivo: ' . self::$theme, 'THEME');

    // HEAD
    self::addOnce('head.after', [self::class, 'renderHeadAssets']);

    // FOOTER
    self::addOnce('footer.after', [self::class, 'renderFooterAssets']);
  }

  public static function renderInlineCss(): string
  {
    if (empty(self::$inlineCss)) {
      return '';
    }

    $out = "<style id=\"theme-inline-css\">\n";

    foreach (self::$inlineCss as $css) {
      $out .= $css . "\n";
    }

    $out .= "</style>\n";

    return $out;
  }
  private static function renderHeadAssets(): string
  {
    $out = '';

    foreach (self::resolve(self::$assets['css']) as $asset) {
      $out .= sprintf('<link rel="stylesheet" href="%s" id="%s">%s', $asset['url'], $asset['id'], PHP_EOL);
    }

    // ✅ inline css
    $out .= self::renderInlineCss();

    foreach (self::resolve(self::$assets['js_head']) as $asset) {
      $out .= sprintf('<script src="%s" id="%s"></script>%s', $asset['url'], $asset['id'], PHP_EOL);
    }

    return $out;
  }
  private static function renderInlineJs(): string
  {
    if (empty(self::$assets['inline_js'])) {
      return '';
    }

    $out = '';

    foreach (self::$assets['inline_js'] as $id => $asset) {
      $out .= "<script id=\"" . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . "\">\n";
      $out .= $asset['code'] . "\n";
      $out .= "</script>\n";
    }

    return $out;
  }
  private static function renderFooterAssets(): string
  {
    $out = '';

    foreach (self::resolve(self::$assets['js_footer']) as $asset) {
      $out .= '<script src="' . $asset['url'] . '" id="' . $asset['id'] . '"></script>' . "\n";
    }

    // 🔥 QUESTO MANCAVA
    $out .= self::renderInlineJs();

    return $out;
  }


  public static function add(string $hook, callable|string $content): void
  {
    if (!isset(self::$hooks[$hook])) {
      return;
    }

    self::$hooks[$hook][] = $content;
  }

  /**
   * Aggiunge contenuto UNA SOLA VOLTA per hook + id
   */
  public static function addOnce(string $hook, callable|string $content): void
  {
    if (!isset(self::$hooks[$hook])) {
      return;
    }

    // 🔑 chiave stabile per qualsiasi tipo di callable
    if (is_string($content)) {
      $key = $hook . '::string::' . md5($content);
    } elseif (is_array($content)) {
      // callable statico: [Class, method]
      $key = $hook . '::callable::' . md5($content[0] . '::' . $content[1]);
    } elseif ($content instanceof \Closure) {
      $key = $hook . '::closure::' . spl_object_id($content);
    } else {
      // oggetto callable
      $key = $hook . '::object::' . spl_object_id($content);
    }

    if (isset(self::$registered[$key])) {
      return;
    }

    self::$registered[$key] = true;
    self::$hooks[$hook][] = $content;
  }

  public static function render(string $hook): string
  {
    \App\Debug::log("Render hook: {$hook}", 'THEME');
    if (!isset(self::$hooks[$hook])) {
      return '';
    }

    $output = '';

    foreach (self::$hooks[$hook] as $item) {
      $output .= is_callable($item) ? $item() : $item;
    }

    return $output;
  }
  public static function addCss(
    string $url,
    string $id,
    array $deps = []
  ): void {
    self::$assets['css'][$id] = [
      'url'  => $url,
      'deps' => $deps,
    ];
  }

  public static function addJsHead(
    string $url,
    string $id,
    array $deps = []
  ): void {
    self::$assets['js_head'][$id] = [
      'url'  => $url,
      'deps' => $deps,
    ];
  }

  public static function addJsFooter(
    string $url,
    string $id,
    array $deps = []
  ): void {
    self::$assets['js_footer'][$id] = [
      'url'  => $url,
      'deps' => $deps,
    ];
  }

  public static function addInlineJs(
    string $id,
    string $code,
    array $deps = []
  ): void {
    self::$assets['inline_js'][$id] = [
      'id'   => $id,
      'code' => trim($code),
      'deps' => $deps,
    ];
  }
  public static function addInlineCss(?string $id, string $css): void
  {
    $key = $id ?? md5($css);

    if (isset(self::$inlineCss[$key])) {
      return;
    }

    self::$inlineCss[$key] = trim($css);
  }
  private static function resolve(array $assets): array
  {
    $resolved = [];
    $visited  = [];

    $visit = function ($id) use (&$visit, &$resolved, &$visited, $assets) {
      if (isset($visited[$id])) {
        return;
      }

      $visited[$id] = true;

      if (!isset($assets[$id])) {
        return;
      }

      foreach ($assets[$id]['deps'] as $dep) {
        $visit($dep);
      }

      $resolved[] = array_merge(
        ['id' => $id],
        $assets[$id]
      );
    };

    foreach (array_keys($assets) as $id) {
      $visit($id);
    }

    return $resolved;
  }
  public static function loadFunctions(): void
  {
    $functions = self::$basePath . '/functions.php';

    if (file_exists($functions)) {

      require_once $functions;
    } else {
      Debug::log('Nessun functions.php trovato nel tema', 'THEME');
    }
  }
  public static function setTwig(\Twig\Environment $twig): void
  {
    self::$twig = $twig;
  }
  public static function renderTemplate(string $template, array $vars = []): string
  {
    if (!self::$twig) {
      throw new \RuntimeException('Twig non inizializzato nel ThemeManager');
    }

    return self::$twig->render("@theme/$template", $vars);
  }
}
