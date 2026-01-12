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
  private static ?Plugin $instance = null;
  /**
   * Carica tutti i plugin attivi nella cartella dei plugin
   *
   * @return void
   */
  public static function loadAll(): Plugin
  {
    if (self::$initialized && self::$instance !== null) {
      Debug::log("⚠️ Plugin::loadAll() già eseguito, skip.", "PLUGIN");
      return self::$instance;
    }

    self::$initialized = true;
    self::$instance = new self();
    self::$instance->load();

    return self::$instance;
  }
  public static function count(): int
  {
    return self::$instance
      ? count(self::$instance->plugins)
      : 0;
  }
  public static function getPlugins(): array
  {
    return self::$instance
      ? self::$instance->plugins
      : [];
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

      if (!file_exists($pluginPath) || !file_exists($configPath)) {
        Debug::log("⚠️ Plugin [{$folder}] ignorato: file mancanti", 'PLUGIN');
        continue;
      }

      $config = json_decode((string)file_get_contents($configPath), true);
      if (!$config) {
        Debug::log("⚠️ Plugin [{$folder}] ignorato: config.json non valido", 'PLUGIN');
        continue;
      }

      $active   = (bool)($config['active'] ?? false);
      $class    = "\\App\\Plugins\\{$folder}\\{$folder}";
      $instance = null;

      // 👉 istanzia SOLO se attivo
      if ($active) {
        require_once $pluginPath;

        if (class_exists($class)) {
          $instance = new $class($folder);
          Debug::log("✅ Plugin [{$folder}] attivo e istanziato", 'PLUGIN');
        } else {
          Debug::log("❌ Classe plugin non trovata: {$class}", 'PLUGIN');
        }
      } else {
        Debug::log("⏸ Plugin [{$folder}] disattivato", 'PLUGIN');
      }

      // ✅ SEMPRE aggiunto alla lista
      $this->plugins[] = [
        'label'    => $config['label'],
        'name'     => $folder,
        'slug'     => strtolower($folder),
        'class'    => $class,
        'instance' => $instance,   // null se disattivo
        'path'     => "{$pluginDir}/{$folder}",
        'config'   => $config,
        'active'   => $active,
      ];
    }

    Debug::log("📦 Totale plugin rilevati: " . count($this->plugins), 'PLUGIN');
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

        // 🔒 plugin disattivo o non istanziato → skip
        if (
          empty($plugin['active']) ||
          empty($plugin['instance']) ||
          !is_object($plugin['instance'])
        ) {
          continue;
        }

        $instance = $plugin['instance'];

        if (method_exists($instance, 'register')) {
          $instance->register($router, $twigManager, $menuManager);

          Debug::log(
            "🔗 Route registrate per plugin: {$plugin['name']}",
            'PLUGIN'
          );
        } else {
          Debug::log(
            "⚠️ Plugin {$plugin['name']} non implementa register()",
            'PLUGIN'
          );
        }
      }
      Debug::log("✅ Registrazione route completata", 'PLUGIN');
    } else {
      Debug::log("🔌 Registrazione rotte plugin (solo API, niente Twig/Menu)", "PLUGIN");
    }
  }
}
