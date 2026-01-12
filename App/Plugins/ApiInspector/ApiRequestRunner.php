<?php

namespace App\Plugins\ApiInspector;

use Core\Router;

final class ApiRequestRunner
{
  public function run(Router $router, string $uri): mixed
  {
    return $router->dispatch(trim($uri, '/'));
  }
}
