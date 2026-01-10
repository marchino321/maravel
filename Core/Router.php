<?php

declare(strict_types=1);

namespace Core;

use App\Config;
use App\Debug;
use Core\View\TwigManager;
use Core\View\MenuManager;
use ReflectionClass;
use Throwable;

if (!defined("CLI_MODE")) {
  defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}

/**
 * Router principale dell'applicazione.
 *
 * - Gestione routing base
 * - Gestione plugin
 * - Gestione API v1 (JSON)
 * - Controllo accessi (aree private / API)
 * - Iniezione automatica di TwigManager e MenuManager
 */
class Router
{
  /** @var array<string, array{callback: callable, namespace: string}> */
  private array $routes = [];

  /** @var array<string, callable> */
  private array $pluginRoutes = [];

  private ?TwigManager $twigManager;
  private ?MenuManager $menuManager;

  public function __construct(?TwigManager $twigManager = null, ?MenuManager $menuManager = null)
  {
    $this->twigManager = $twigManager;
    $this->menuManager = $menuManager;

    Debug::log(
      sprintf(
        "✅ Router inizializzato (Twig=%s, Menu=%s)",
        $twigManager ? 'OK' : 'NULL',
        $menuManager ? 'OK' : 'NULL'
      ),
      'ROUTER'
    );
  }

  protected function convertToStudlyCaps(string $string): string
  {
    return str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
  }

  protected function convertToCamelCase(string $string): string
  {
    return lcfirst($this->convertToStudlyCaps($string));
  }

  public function add(string $pattern, callable $callback, string $namespace = 'App\\Controllers'): void
  {
    $this->routes[$pattern] = [
      'callback'  => $callback,
      'namespace' => $namespace
    ];
    Debug::log("➕ Aggiunta route normale: {$pattern} (ns: {$namespace})", 'ROUTER');
  }

  public function addPluginRoute(string $pattern, callable $callback): void
  {
    $this->pluginRoutes[$pattern] = $callback;
    Debug::log("🔌 Aggiunta route plugin: {$pattern}", 'ROUTER');
  }

  /**
   * Dispatch della richiesta
   *
   * @param string $uri
   * @return string|null
   */
  public function dispatch(string $uri): ?string
  {
    Debug::log("🚦 Dispatch URI: {$uri}", 'ROUTER');
    $isApi = false;

    try {
      $uri = trim($uri, '/');
      if ($uri === '') {
        $uri = 'home/index';
      }
      // ==============================
      // API versionate (v1, v2, v3...)
      // ==============================
      if (preg_match('#^api/v(\d+)(/.*)?$#', $uri, $matches)) {
        $version = (int)$matches[1];   // es: 1, 2, 3...
        $isApi   = true;
        header('Content-Type: application/json');

        $parts = explode('/', $uri);
        array_shift($parts); // api
        array_shift($parts); // vX

        $controllerName  = $this->convertToStudlyCaps($parts[0] ?? 'Test');
        $method          = $this->convertToCamelCase($parts[1] ?? 'index');
        $params          = array_slice($parts, 2);

        if ($version === 1) {
          // namespace per v1 (compatibilità)
          $controllerClass = "App\\Controllers\\Api\\{$controllerName}";
        } else {
          // namespace per v2, v3, ...
          $controllerClass = "App\\Controllers\\Api\\V{$version}\\{$controllerName}";
        }

        if (!class_exists($controllerClass)) {
          echo json_encode([
            "success" => false,
            "error"   => "💥 Spiacente, Api non esistente"
          ], JSON_UNESCAPED_UNICODE);
          return null;
        }

        try {
          $controller = new $controllerClass();
          Debug::log("➡️ API v{$version}: {$controllerClass}::{$method} " . json_encode($params), 'API');

          if (!method_exists($controller, $method)) {
            $method = 'index';
          }

          call_user_func_array([$controller, $method], $params);
          $this->logHttpResponse(200, "{$controllerClass}::{$method}");
        } catch (Throwable $e) {
          $this->sendErrorResponse(500, "💥 Errore API: " . $e->getMessage());
          Debug::log("Trace: " . $e->getTraceAsString(), 'ERROR');
          echo json_encode([
            'success' => false,
            'error'   => "💥 Metodo non conosciuto!",
          ], JSON_UNESCAPED_UNICODE);
        }
        return null;
      }


      // ==============================
      // Plugin routes
      // ==============================
      foreach ($this->pluginRoutes as $pattern => $callback) {
        $regex = preg_replace('#\{(\w+)\}#', '([^/]+)', $pattern);
        $regex = "#^$regex(?:/(.*))?$#";

        if (preg_match($regex, $uri, $matches)) {
          array_shift($matches);
          $params = [];
          foreach ($matches as $m) {
            if ($m !== '' && $m !== null) {
              $params = array_merge($params, explode('/', $m));
            }
          }
          Debug::log("🔌 Route plugin match: {$pattern} → " . json_encode($params), 'ROUTER');
          call_user_func_array($callback, $params);
          $this->logHttpResponse(200, "Route plugin {$pattern}");
          return null;
        }
      }

      // ==============================
      // Controller/metodo standard
      // ==============================
      $segments = explode('/', $uri);
      $first    = strtolower($segments[0] ?? 'home');

      if (isset(Config::$NAMESPACES[$first])) {
        [$namespace, $type] = Config::$NAMESPACES[$first];
        $controllerName     = $this->convertToStudlyCaps($segments[1] ?? 'Index');
        $method             = $this->convertToCamelCase($segments[2] ?? 'index');
        $params             = array_slice($segments, 2);

        Debug::log("📂 Namespace={$namespace}, Controller={$controllerName}, Metodo={$method}", 'ROUTER');

        // Accesso area privata
        if (in_array($type, ['private', 'ajax'], true) && (!session_id() || !Auth::check())) {
          Debug::log("⛔ Accesso negato a {$uri}", 'ROUTER');
          $_SESSION['redirect_after_login'] = $uri;
          http_response_code(403);
          echo $this->twigManager?->getTwig()->render('Error/Errors.html', [
            'title'     => 'Accesso Negato!',
            'messaggio' => 'Non hai accesso a questa area',
            'risposta'  => ""
          ]);
          return null;
        } else {
          (new \Core\Classi\SessionRefresher())->refresh();
        }
      } else {
        $namespace       = 'App\\Controllers\\';
        $controllerName  = $this->convertToStudlyCaps($segments[0] ?? 'Home');
        $method          = $this->convertToCamelCase($segments[1] ?? 'index');
        $params          = array_slice($segments, 1);
        Debug::log("📂 Default namespace, Controller={$controllerName}, Metodo={$method}", 'ROUTER');
      }

      $controllerClass = $namespace . $controllerName;

      if (!class_exists($controllerClass)) {
        $this->sendErrorResponse(404, "❌ Controller non trovato: {$controllerClass}");
        echo $this->twigManager?->getTwig()->render('Error/Errors.html', [
          'title'     => 'Errore 404',
          'messaggio' => 'Pagina non trovata!'
        ]);
        return null;
      }

      // Istanziazione dinamica
      $reflection = new ReflectionClass($controllerClass);
      $constructorParams = $reflection->getConstructor()?->getParameters() ?? [];
      $args = [];

      foreach ($constructorParams as $param) {
        $type = $param->getType()?->getName() ?? null;
        $args[] = match ($type) {
          TwigManager::class => $this->twigManager,
          MenuManager::class => $this->menuManager,
          default            => null
        };
      }

      $controller = $reflection->newInstanceArgs($args);
      Debug::log("✅ Controller istanziato: {$controllerClass}", 'ROUTER');

      EventManager::dispatch("controller.loaded", $controllerClass, $controller);

      if (!method_exists($controller, $method)) {
        Debug::log("⚠️ Metodo {$method} non trovato, fallback index()", 'ROUTER');
        $result = $controller->index(...$params);
      } else {
        Debug::log("➡️ Call {$controllerClass}::{$method}", 'ROUTER');
        EventManager::dispatch("controller.beforeAction", $controllerClass, $method, $params);

        // Gestione POST + CSRF
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
          $formData = EventManager::dispatch("form.submitted", $this, $_POST);
          if ($formData === false) {
            Debug::log("❌ CSRF non valido", "SECURITY");
            while (ob_get_level()) ob_end_clean();
            header_remove("X-Powered-By");
            http_response_code(419);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'CSRF token non valido']);
            return null;
          }
          if ($formData && is_array($formData)) {
            if (isset($_POST['csrf_token'])) {
              unset($_POST['csrf_token']);
              unset($formData['csrf_token']);
            }

            $_POST = $formData;
          }
        }

        $result = call_user_func_array([$controller, $method], $params);
        EventManager::dispatch("controller.afterAction", $controllerClass, $method, $params, $result);
      }

      Debug::log("✅ Metodo {$method} completato", 'ROUTER');
      $this->logHttpResponse(200, "{$controllerClass}::{$method}");
      EventManager::dispatch("router.afterDispatch", $uri, $result);

      return $result;
    } catch (Throwable $e) {
      Debug::log("❌ Router exception: {$e->getMessage()}", 'ERROR');
      Debug::log("Trace: {$e->getTraceAsString()}", 'ERROR');

      if ($isApi) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
          'success' => false,
          'error'   => $e->getMessage(),
          'file'    => $e->getFile(),
          'line'    => $e->getLine()
        ]);
        return null;
      }

      if ($this->twigManager) {
        http_response_code(Config::$DEBUG_CONSOLE ? 200 : 500);
        return $this->twigManager->getTwig()->render('Error/Errors.html', [
          'title'     => 'Errore 500',
          'messaggio' => '💥 Errore interno'
        ]);
      }
      return null;
    }
  }

  private function sendErrorResponse(int $code, string $message): void
  {
    http_response_code($code);
    Debug::log("📤 HTTP {$code} → {$message}", 'ROUTER');
  }

  private function logHttpResponse(int $code, string $context = ''): void
  {
    Debug::log("📤 HTTP {$code}" . ($context ? " da {$context}" : ""), 'ROUTER');
  }

  public function callAction(string $method, array $params = []): mixed
  {
    EventManager::dispatch("controller.beforeAction", $this, $method, $params);
    $result = call_user_func_array([$this, $method], $params);
    EventManager::dispatch("controller.afterAction", $this, $method, $result);
    return $result;
  }
}
