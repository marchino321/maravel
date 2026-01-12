<?php

declare(strict_types=1);

namespace App\Plugins\ApiInspector;

use App\Config;
use ReflectionClass;
use ReflectionMethod;

final class ApiAnalyzer
{
  public static function scan(): array
  {
    $baseDir = Config::$baseDir . '/App/Controllers/Api';
    $apis = [];

    // =====================
    // API V1 (root Api/*.php)
    // =====================
    foreach (glob($baseDir . '/*.php') as $file) {
      $class = self::classFromFile($file, 'Api');
      if ($class) {
        $apis = array_merge($apis, self::analyzeController($class, 1));
      }
    }

    // =====================
    // API V2+ (Api/V*/ *.php)
    // =====================
    foreach (glob($baseDir . '/V*', GLOB_ONLYDIR) as $versionDir) {
      $version = (int) preg_replace('/[^0-9]/', '', basename($versionDir));
      if ($version < 2) continue;

      foreach (glob($versionDir . '/*.php') as $file) {
        $class = self::classFromFile($file, "Api\\V{$version}");
        if ($class) {
          $apis = array_merge($apis, self::analyzeController($class, $version));
        }
      }
    }

    return $apis;
  }
  private static function analyzeController(string $class, int $version): array
  {
    $rc = new \ReflectionClass($class);
    $items = [];

    foreach ($rc->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
      if ($method->isConstructor() || $method->isStatic()) continue;

      $items[] = [
        'version'    => 'v' . $version,
        'controller' => $rc->getShortName(),
        'method'     => $method->getName(),
        'http'       => self::detectHttp($method->getName()),
        'uri'        => self::guessUriWithParams($class, $method),
      ];
    }

    return $items;
  }
  private static function detectHttp(string $methodName): string
  {
    $m = strtolower($methodName);

    // GET convention
    if ($m === 'index' || str_starts_with($m, 'get') || str_starts_with($m, 'list') || str_starts_with($m, 'show')) {
      return 'GET';
    }

    // POST convention
    if (
      str_starts_with($m, 'post') ||
      str_starts_with($m, 'create') ||
      str_starts_with($m, 'store') ||
      str_starts_with($m, 'save') ||
      str_starts_with($m, 'update') ||
      str_starts_with($m, 'delete') ||
      str_starts_with($m, 'remove') ||
      str_starts_with($m, 'set')
    ) {
      return 'POST';
    }

    // Default (come il tuo router: chiamate senza distinguere)
    return 'POST';
  }
  private static function detectVersion(string $class): int
  {
    // App\Controllers\Api\V2\User
    if (preg_match('#\\\Api\\\V(\d+)\\\#', $class, $m)) {
      return (int) $m[1];
    }

    // App\Controllers\Api\User → v1
    if (str_contains($class, '\\Api\\')) {
      return 1;
    }

    return 0; // non API (opzionale)
  }

  private static function guessUri(string $class, string $method): string
  {
    $version = self::detectVersion($class);

    // Controller shortname -> "ClientiController" -> "clienti"
    $parts = explode('\\', $class);
    $short = end($parts);
    $controller = strtolower(preg_replace('/Controller$/', '', $short));

    // Metodo 그대로 (camelCase -> kebab opzionale se vuoi)
    $action = strtolower($method);

    // index fallback
    if ($action === 'index') {
      return "/api/v{$version}/{$controller}";
    }

    return "/api/v{$version}/{$controller}/{$action}";
  }

  private static function guessUriWithParams(string $class, \ReflectionMethod $rm): string
  {
    $version = self::detectVersion($class);

    $short = (new \ReflectionClass($class))->getShortName();
    $controller = strtolower(preg_replace('/Controller$/', '', $short));
    $action = strtolower($rm->getName());

    $base = ($action === 'index')
      ? "/api/v{$version}/{$controller}"
      : "/api/v{$version}/{$controller}/{$action}";

    // parametri extra della route
    $params = [];
    foreach ($rm->getParameters() as $p) {
      $params[] = '{' . $p->getName() . '}';
    }

    return $params ? ($base . '/' . implode('/', $params)) : $base;
  }

  private static function classFromFile(string $file, string $subNs): ?string
  {
    $name = basename($file, '.php');
    $class = "App\\Controllers\\{$subNs}\\{$name}";
    return class_exists($class) ? $class : null;
  }
}
