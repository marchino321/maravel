<?php

namespace App\Controllers\Api;

use Core\ApiController;
use App\Debug;

class Clienti extends ApiController
{
    /**
     * GET /api/v1/clienti/elenco
     */
    public function elenco(): void
    {
        Debug::log("API Clienti::elenco chiamata", "API");

        $data = [
            ['id' => 1, 'nome' => 'Mario Rossi'],
            ['id' => 2, 'nome' => 'Luca Bianchi'],
        ];

        $this->jsonResponse($data);
    }

    /**
     * GET /api/v1/clienti/dettaglio/{id}
     */
    public function Dettaglio(...$params): void
    {
        $id = $params[0] ?? 0;
        Debug::log("API Clienti::dettaglio id={$id}", "API");

        $cliente = [
            'id'    => $id,
            'nome'  => "Cliente {$id}",
            'email' => "cliente{$id}@example.com"
        ];
        if( $id === 0){
            $this->jsonError("ID mancante");
        }else{
            $this->jsonResponse($cliente);
        }
        
    }
    public function index(...$params)
    {
        //$this->Dettaglio($params);
        call_user_func_array([$this, 'Dettaglio'], $params);
    }
}
