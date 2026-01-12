<?php

namespace App\Controllers\Api;

use Core\ApiController;
use App\Debug;

class Test extends ApiController
{
  public function index(...$params): void
  {
    Debug::log("API Test::index chiamata con params=" . json_encode($params), 'API');
    $this->apiResponse([
      'message'   => 'Endpoints disponibili',
      'endpoints' => [
        'GET /api/v1/clienti/dettaglio/{id?}',
        'GET /api/v2/clienti/dettaglio/{id?}',
      ],
      'params'    => $params,
    ]);
  }
}
