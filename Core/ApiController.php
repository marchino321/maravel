<?php

namespace Core;

use App\Debug;

abstract class ApiController extends PluginController
{
    public function __construct(?string $pluginName = null)
    {
        parent::__construct($pluginName);

        // ✅ Verifica API Key
        Api::check();
        Debug::log("🔑 API autorizzata: " . static::class, "API");
    }
    protected function jsonResponse(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'success' => true,
            'data'    => $data
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function jsonError(string $message, int $status = 400): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'success' => false,
            'error'   => [
                'code'    => $status,
                'message' => $message
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}