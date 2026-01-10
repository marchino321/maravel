<?php

namespace Core\Classi;

use App\Config;
use Core\View\TwigManager;

if (!defined("CLI_MODE")) {
    defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}
/**
 * Gestione messaggi flash
 * - Memorizza messaggi in sessione
 * - Fornisce tipi predefiniti (SUCCESS, DANGER, INFO, WARNING)
 * - Funzione Twig per stampare messaggi
 * - Hook per aggiungere messaggi da plugin o controller
 */
class GetHeaders
{


    public static function GetJsonHeaderClean(int $statusCode = 200)
    {
        while (ob_get_level()) ob_end_clean();
        header_remove("X-Powered-By");
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
    }


    public static function JsonResponse(array $data, int $statusCode = 200): void
    {
        self::GetJsonHeaderClean($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    



}
