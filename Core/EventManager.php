<?php

namespace Core;

use App\Debug;
use App\Config;

if (!defined("CLI_MODE")) {
    defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}

class EventManager
{
    /** @var array<string,array<callable>> */
    private static array $listeners = [];

    /** @var array<int,string> */
    private static array $firedEvents = [];

    /**
     * Registra un listener su un evento
     */
    public static function on(string $event, callable $callback): void
    {
        self::$listeners[$event][] = $callback;
        Debug::log("👂 Listener registrato su evento: {$event}", 'EVENTI');
    }

    /**
     * Lancia un evento
     * Ritorna l'ultimo valore restituito dai listener (se presente)
     */
    public static function dispatch(string $event, ...$payload)
    {
        self::$firedEvents[] = $event;

        if (!isset(self::$listeners[$event])) {
            Debug::log("⚠️ Nessun listener per evento: {$event}", 'EVENTI');
            Debug::log("🎉 Evento creato: {$event}", 'EVENTI');
            return null;
        }

        $result = null;
        foreach (self::$listeners[$event] as $callback) {
            try {
                $res = $callback(...$payload);
                if ($res !== null) {
                    $result = $res;
                }
            } catch (\Throwable $e) {
                Debug::log("❌ Errore listener evento '{$event}': " . $e->getMessage(), 'EVENTI');
            }
        }

        Debug::log("🎉 Evento creato: {$event}", 'EVENTI');
        return $result;
    }

    /**
     * Logga alla console debug la lista completa degli eventi dispatchati
     */
    public static function debugFiredEvents(): void
    {
        if (empty(self::$firedEvents)) {
            Debug::log("ℹ️ Nessun evento dispatchato", 'EVENTI');
            return;
        }

        $lista = implode(', ', self::$firedEvents);
        Debug::log("📋 Lista eventi dispatchati: {$lista}", 'EVENTI');
    }
}
