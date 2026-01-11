<?php

namespace App\Install;

use Core\MigrationManager;
use App\Config;
use mysqli;



class Installer
{
  /* ==========================================================
   * CHECK REQUISITI
   * ========================================================== */
  public function checkRequirements(): array
  {
    return [
      'php' => [
        'version' => PHP_VERSION,
        'ok' => version_compare(PHP_VERSION, '8.2.0', '>=')
      ],
      'mysql' => [
        'enabled' => extension_loaded('mysqli')
      ],
      'composer' => [
        'installed' => $this->checkComposer()
      ],
      'ssl' => [
        'enabled' => $this->checkSSL()
      ]
    ];
  }

  private function checkComposer(): bool
  {
    if (file_exists(__DIR__ . '/../../composer.phar')) {
      return true;
    }

    $which = @shell_exec('which composer');
    return !empty($which);
  }

  private function checkSSL(): bool
  {
    return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
  }

  /* ==========================================================
   * INSTALLAZIONE COMPLETA
   * ========================================================== */
  public function installDatabase(array $data): array
  {

    /* =========================
     * 1. Connessione DB
     * ========================= */
    try {
      $mysqli = @new mysqli(
        $data['db_host'],
        $data['db_user'],
        $data['db_pass']
      );
    } catch (\Throwable $th) {

      return [
        'status'  => 'error',
        'message' => "Connessione fallita: $th"
      ];
      exit;
    }

    /* =========================
     * 2. Creazione Database
     * ========================= */
    $dbName = $mysqli->real_escape_string($data['db_name']);

    if (!$mysqli->query("
      CREATE DATABASE IF NOT EXISTS `$dbName`
      CHARACTER SET utf8mb4
      COLLATE utf8mb4_unicode_ci
    ")) {
      return [
        'status'  => 'error',
        'message' => 'Errore creazione database: ' . $mysqli->error
      ];
    }

    $mysqli->select_db($dbName);
    $mysqli->set_charset('utf8mb4');

    /* =========================
     * 3. Preparazione config
     * ========================= */
    $salt = bin2hex(random_bytes(32));
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    $headersApi = array_values(array_filter(
      array_map(
        'trim',
        preg_split('/\r\n|\r|\n/', $data['header_api'])
      )
    ));

    $config = [
      "dbConfig"      => [
        "host"        => $data['db_host'],
        "dbname"      => $data['db_name'],
        "user"        => $data['db_user'],
        "pass"        => $data['db_pass']
      ],
      "HEADER_API"    => $headersApi,
      "DEBUG_CONSOLE" => false,
      "site_name"     => $data['site_name'],
      "Logo_App"      => $data['logo_app'],
      "ABS_KEY"       => $data['abs_key'],
      "SALT"          => $salt,
      "LinkMain"      => 'https://' . $host
    ];

    $configPath = dirname(__DIR__, 2) . '/ConfigFiles/config.local.json';

    if (!is_dir(dirname($configPath))) {
      mkdir(dirname($configPath), 0755, true);
    }

    file_put_contents(
      $configPath,
      json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

    /* =========================
     * 4. Bootstrap Config
     * ========================= */
    Config::loadFromJson($configPath);

    /* =========================
     * 5. Esecuzione Migrazioni
     * ========================= */
    try {
      $migrationManager = new MigrationManager();
      $executed = $migrationManager->run();
    } catch (\Throwable $e) {
      return [
        'status'  => 'error',
        'message' => 'Errore migrazioni: ' . $e->getMessage()
      ];
    }

    return [
      'status'     => 'ok',
      'message'    => '✅ Installazione completata',
      'migrations' => $executed
    ];
  }
}
