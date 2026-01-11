<?php

namespace App;

use App\Debug;

class Config
{
  // -------------------------------
  // Config dinamiche lette dal JSON
  // -------------------------------
  public static array $HEADER_API = [];    // Header consentiti per API
  public static array $NAMESPACES = [
    'api'     => ['App\\Controllers\\Api\\', 'api/v1'],
    'api_2'   => ['App\\Controllers\\Api\\V2\\', 'api/v2'],
    'private' => ['App\\Controllers\\Private\\', 'private'],
    'ajax'    => ['App\\Controllers\\Ajax\\', 'ajax'],
    'front'   => ['App\\Controllers\\', 'front'],
  ];
  public static array $dbConfig = [];      // Config database
  public static array $hooks = [];         // Hook dinamici

  /**
   * Chiave della sessione che identifica l'utente loggato
   */
  public static string $SESSION_USER_KEY = 'idUtenteAutoIncrement';

  public static string $ID_SUPER_ADMIN = '1';

  // -------------------------------
  // Percorsi e debug (di default, possono essere sovrascritti dal JSON)
  // -------------------------------
  public static string $baseDir   = __DIR__ . "/../";
  public static string $pluginDir = __DIR__ . '/Plugins';
  public static string $viewsDir  = __DIR__ . '/Views';
  public static string $assetsDir = '/public';
  public static string $logDir    = __DIR__ . '/../logs';
  public static string $configDir = __DIR__ . '/../ConfigFiles';
  public static string $site_name       = 'Family Nest';
  public static string $env       = 'dev';
  public static array $custom_config = [];
  public static bool $DEBUG_CONSOLE = true;
  public static string $Logo_App = "";
  public static string $SALT = "";
  public static string $link_footer = "https://marcodattisi.it";
  public static string $label_link = "Marco Dattisi";
  public static string $design_footer = "Developing by";
  public static string $LinkMain = "https://hd.marcodattisi.it";
  public static string $LinkUpdate = "hd.marcodattisi.it";
  public static string $ABS_KEY = "MARAVEL2026";

  public static string $LOGO_APPINTESTAZIONE_PDF = "";

  public static string $client_key_wc = "";

  public static string $secret_key_wc = "";

  public static string $base_url_wc = "";
  // -------------------------------
  // Hook dinamici
  // -------------------------------
  public static function addHook(\Closure $hook): void
  {
    self::$hooks[] = $hook;
  }

  public static function runHooks(): void
  {
    foreach (self::$hooks as $hook) {
      if ($hook instanceof \Closure) {
        Debug::log("Esecuzione hook: closure a linea " . (new \ReflectionFunction($hook))->getStartLine(), 'APP');
        $hook(self::class);
      }
    }
  }

  public static function addApiHeader(string $header): void
  {
    $header = strtoupper($header);
    if (!in_array($header, self::$HEADER_API, true)) {
      self::$HEADER_API[] = $header;
    }
  }

  public static function addNamespace(string $key, string $namespace, string $type = 'front'): void
  {
    self::$NAMESPACES[$key] = [$namespace, $type];
  }

  public static function setConfig(\Closure $hook): void
  {
    $hook(self::class);
  }

  // -------------------------------
  // Lettura da file JSON
  // -------------------------------
  public static function loadFromJson(string $file): void
  {
    if (!file_exists($file)) return;

    $json = file_get_contents($file);
    $data = json_decode($json, true);

    if (!is_array($data)) {
      Debug::log("Errore parsing JSON: $file", 'CONFIG');
      return;
    }

    foreach ($data as $key => $value) {
      if (property_exists(self::class, $key)) {
        $current = self::${$key};

        // Se entrambi sono array
        if (is_array($current) && is_array($value)) {
          // Se il JSON è un array vuoto → ignora, tieni i default
          if (empty($value)) {
            continue;
          }
          // Unisce: i default rimangono, il JSON sovrascrive/aggiunge
          self::${$key} = array_merge($current, $value);
        } else {
          self::${$key} = $value;
        }

        Debug::log("Configurazione caricata da JSON: $key", 'CONFIG');
      }
    }

    Debug::log("Configurazione JSON processata: $file", 'CONFIG');
  }
}
