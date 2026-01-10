<?php

namespace Core;

use Twig\Environment;
use App\Debug;
use App\Config;

class SafeTwigEnvironment extends Environment
{
    public function render($name, array $context = []): string
    {
        try {
            return parent::render($name, $context);
        } catch (\Throwable $e) {
            if (Config::$DEBUG_CONSOLE) {
                Debug::log("❌ Errore rendering view: " . $e->getMessage(), "ERROR");
                Debug::log("File: " . $e->getFile() . " Linea: " . $e->getLine(), "ERROR");

                return "<pre style='color:red; font-weight:bold; background:#fff0f0; padding:10px;'>"
                    . "Errore rendering view <b>{$name}</b>\n\n"
                    . $e->getMessage() . "\n\n"
                    . "File: " . $e->getFile() . " (line " . $e->getLine() . ")"
                    . "</pre>";
            }

            throw $e; // in produzione errore normale
        }
    }
}
