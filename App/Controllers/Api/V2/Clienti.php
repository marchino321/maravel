<?php

namespace App\Controllers\Api\V2;

use Core\ApiController;

class Clienti extends ApiController
{
  public function elenco(): void
  {
    //$this->requireAuth();

    // 🔄 nuova logica per v2
    $clienti = [
      ['id' => 1, 'nome' => 'Mario Rossi', 'email' => 'mario@rossi.it', 'telefono' => '123456789'],
      ['id' => 2, 'nome' => 'Luigi Bianchi', 'email' => 'luigi@bianchi.it', 'telefono' => '987654321'],
    ];

    $this->apiResponse($clienti);
  }

  public function dettaglio(int $id): void
  {
    if (!$id) {
      $this->jsonError("ID mancante");
    }

    // 🔄 v2 include più dettagli
    $cliente = [
      'id' => $id,
      'nome' => 'Mario Rossi',
      'email' => 'mario@rossi.it',
      'telefono' => '123456789',
      'indirizzo' => 'Via Roma 10, Napoli'
    ];

    $this->apiResponse($cliente);
  }
}
