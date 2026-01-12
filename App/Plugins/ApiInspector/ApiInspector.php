<?php

declare(strict_types=1);

namespace App\Plugins\ApiInspector;

use App\Debug;
use Core\Auth;
use Core\PluginController;
use Core\Router;
use Core\View\TwigManager;
use Core\View\MenuManager;
use Core\View\Page;

class ApiInspector extends PluginController
{
  private MenuManager $menuManager;
  public function __construct()
  {
    parent::__construct('ApiInspector');
  }

  public function register(Router $router, TwigManager $twigManager, ?MenuManager $menuManager = null): void
  {
    // 🔹 registra namespace twig del plugin
    // $twigManager->addPath(
    //   __DIR__ . '/Views',
    //   'ApiInspector'
    // );

    if ($menuManager) {

      $menuManager->addMenuItem(
        'api-inspector',
        'API Inspector',
        '/api-inspector',
        ['Admin'],
        1,
        null,
        'mdi mdi-api'
      );
      // \App\Debug::log('DOPO addMenuItem, count=' . count($menuManager->getMenu()), 'API-INSPECTOR');
    }
    $router->addPluginRoute('api-inspector', function () use ($twigManager) {
      echo $twigManager->getTwig()->render(
        '@ApiInspector/index.html',
        ['apis' => ApiAnalyzer::scan()]
      );
    });
    $router->addPluginRoute('api-inspector/execute', function () use ($router) {

      if (!Auth::checkSuperAdmin()) {
        http_response_code(403);
        return [
          'success' => false,
          'error'   => 'Accesso negato'
        ];
      }

      $uri = $_POST['uri'] ?? null;

      if (!$uri) {
        return [
          'success' => false,
          'error'   => 'URI mancante'
        ];
      }

      $runner = new ApiRequestRunner();
      return $runner->run($router, $uri);
    });
  }
}
