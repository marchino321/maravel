<?php

declare(strict_types=1);

namespace Core;

use App\Config;
use App\Debug;
use Core\View\TwigManager;
use Core\View\MenuManager;

if (!defined("CLI_MODE")) {
    defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}
/**
 * Gestione dei plugin dell'applicazione.
 *
 * - Carica automaticamente i plugin attivi
 * - Registra le loro route su Router
 * - Inietta TwigManager e MenuManager nei plugin
 * - Logging dettagliato con Debug::log()
 */
class Plugin
{
    /** @var array<int,object> Elenco istanze plugin caricati */
    private array $plugins = [];
    private static bool $initialized = false;
    /**
     * Carica tutti i plugin attivi nella cartella dei plugin
     *
     * @return void
     */
    public static function loadAll(): Plugin
    {
        static $instance = null;

        if (self::$initialized && $instance !== null) {
            Debug::log("⚠️ Plugin::loadAll() già eseguito, skip.", "PLUGIN");
            return $instance;
        }

        self::$initialized = true;
        $instance = new self();
        $instance->load();

        return $instance;
    }
    public function load(): void
    {
        $pluginDir = Config::$pluginDir;
        
        if (!is_dir($pluginDir)) {
            Debug::log("❌ Directory plugin non trovata: {$pluginDir}", 'PLUGIN');
            return;
        }

        Debug::log("🔍 Avvio scansione plugin in: {$pluginDir}", 'PLUGIN');

        foreach (scandir($pluginDir) as $folder) {
            if ($folder === '.' || $folder === '..') {
                continue;
            }

            $pluginPath = "{$pluginDir}/{$folder}/{$folder}.php";
            $configPath = "{$pluginDir}/{$folder}/config.json";

            if (!file_exists($pluginPath)) {
                Debug::log("⚠️ Plugin [{$folder}] ignorato: file principale mancante ({$pluginPath})", 'PLUGIN');
                continue;
            }

            if (!file_exists($configPath)) {
                Debug::log("⚠️ Plugin [{$folder}] ignorato: file config.json mancante", 'PLUGIN');
                continue;
            }

            $config = json_decode((string)file_get_contents($configPath), true);
            if (!$config) {
                Debug::log("⚠️ Plugin [{$folder}] ignorato: config.json non valido", 'PLUGIN');
                continue;
            }

            // Attivo solo se flag active è true
            if (($config['active'] ?? false) === true) {
                require_once $pluginPath;
                $class = "\\App\\Plugins\\{$folder}\\{$folder}";

                if (class_exists($class)) {
                   // $instance = new $class();
                    $instance = new $class($folder); // passa il nome del plugin
                    $this->plugins[] = $instance;

                    Debug::log("✅ Plugin [{$folder}] caricato con successo (classe: {$class})", 'PLUGIN');
                } else {
                    Debug::log("❌ Classe plugin non trovata: {$class}", 'PLUGIN');
                }
            } else {
                Debug::log("⏸ Plugin [{$folder}] disattivato da config.json", 'PLUGIN');
            }
        }

        Debug::log("📦 Totale plugin caricati: " . count($this->plugins), 'PLUGIN');
    }

    /**
     * Registra le route dei plugin e passa TwigManager + MenuManager
     *
     * @param Router $router
     * @param TwigManager $twigManager
     * @param MenuManager $menuManager
     * @return void
     */
    public function registerRoutes(Router $router, TwigManager $twigManager, MenuManager $menuManager): void
    {
        Debug::log("🔗 Avvio registrazione route dei plugin...", 'PLUGIN');


        // Se i plugin hanno bisogno di Twig/Menu li uso, altrimenti no
        if ($twigManager && $menuManager) {
            Debug::log("🔌 Registrazione rotte plugin con Twig/Menu", "PLUGIN");
            foreach ($this->plugins as $plugin) {
                if (method_exists($plugin, 'register')) {
                    $plugin->register($router, $twigManager, $menuManager);

                    Debug::log("🔗 Route registrate per plugin: " . get_class($plugin), 'PLUGIN');
                } else {
                    Debug::log("⚠️ Plugin " . get_class($plugin) . " non implementa metodo register()", 'PLUGIN');
                }
            }
            Debug::log("✅ Registrazione route completata", 'PLUGIN');
        } else {
            Debug::log("🔌 Registrazione rotte plugin (solo API, niente Twig/Menu)", "PLUGIN");
        }


        
    }
}
