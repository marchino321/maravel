<?php

namespace Core;

use App\Config;
use App\Debug;
use PDO;

class Api
{
    public static function check(): bool
    {
        // 🔒 1) Verifica se è una chiamata API
        $isApiCall = str_starts_with(trim($_SERVER['REQUEST_URI'], '/'), 'api/');
        if (!$isApiCall) {
            return false;
        }

        // 🔑 2) Recupera header in lowercase
        $headers = array_change_key_case(getallheaders(), CASE_LOWER);
        $key     = null;

        foreach (Config::$HEADER_API as $h) {
            if (isset($headers[strtolower($h)])) {
                $key = trim($headers[strtolower($h)]);
                break;
            }
        }

        
        if(!$key){
            Debug::log("🔑 Header API mancante", "API");
            return self::unauthorized("Header API mancante");
        }
        // ✅ Verifica su DB la validità della chiave DAFARE in futuro nel caso ho bisogno
        if (!isValidApiKey($key) ) {
            return self::unauthorized("API sconosciuta!");
        }
        return true;
        
    }

    /**
     * Risposta JSON 401 standardizzata
     */
    private static function unauthorized(string $message): bool
    {
        header('Content-Type: application/json; charset=utf-8', true, 401);
        echo json_encode([
            'success' => false,
            'error'   => [
                'code'    => 'AUTH_401',
                'message' => $message
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}