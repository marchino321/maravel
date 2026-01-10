<?php

namespace Core;

use App\Config;
use PDO;
use RuntimeException;

class MigrationManager
{
  private string $migrationDir;
  private PDO $db;

  public function __construct()
  {
    $this->migrationDir = Config::$baseDir . '/migrations';

    if (!is_dir($this->migrationDir)) {
      throw new RuntimeException("Directory migrations non trovata");
    }

    // PDO coerente con tutto il framework
    $this->db = new PDO(
      sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        Config::$dbConfig['host'],
        Config::$dbConfig['dbname']
      ),
      Config::$dbConfig['user'],
      Config::$dbConfig['pass'],
      [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
      ]
    );
  }

  /**
   * Ritorna tutte le migration PHP ordinate
   */
  public function getAllMigrations(): array
  {
    $files = array_filter(
      scandir($this->migrationDir),
      fn($f) => preg_match('/^\d+_.*\.php$/', $f)
    );

    sort($files, SORT_NATURAL);

    return array_values($files);
  }

  /**
   * Esegue TUTTE le migration (CLI / installer)
   */
  public function run(): array
  {
    $executed = [];

    foreach ($this->getAllMigrations() as $file) {
      $path = $this->migrationDir . '/' . $file;

      $migration = require $path;

      if (
        !is_array($migration) ||
        !isset($migration['up']) ||
        !is_callable($migration['up'])
      ) {
        throw new \RuntimeException("Migration non valida: {$file}");
      }

      try {
        // ⚠️ NESSUNA TRANSAZIONE (MySQL DDL auto-commit)
        $migration['up']($this->db);
        $executed[] = $file;
      } catch (\Throwable $e) {
        throw new \RuntimeException(
          "Errore nella migration {$file}: " . $e->getMessage()
        );
      }
    }

    return $executed;
  }

  /**
   * (OPZIONALE – FUTURO)
   * Rollback completo
   */
  public function rollbackAll(): void
  {
    $files = array_reverse($this->getAllMigrations());

    foreach ($files as $file) {
      $path = $this->migrationDir . '/' . $file;
      $migration = require $path;

      if (isset($migration['down']) && is_callable($migration['down'])) {
        $this->db->beginTransaction();
        try {
          $migration['down']($this->db);
          $this->db->commit();
        } catch (\Throwable $e) {
          $this->db->rollBack();
          throw $e;
        }
      }
    }
  }
  /* =====================================================
     * EXPORT SCHEMA (ADMIN ONLY)
     * ===================================================== */

  public function exportCurrentSchema(): string
  {
    $dir = Config::$baseDir . '/MigrationsSQL';

    if (!is_dir($dir)) {
      mkdir($dir, 0755, true);
    }

    $timestamp = date('Y_m_d_His');
    $file = $dir . "/schema_{$timestamp}.sql";

    // check mysqldump
    if (!shell_exec('which mysqldump')) {
      throw new \RuntimeException('mysqldump non disponibile sul server');
    }

    $cmd = sprintf(
      'mysqldump -h%s -u%s -p%s %s > %s',
      escapeshellarg(Config::$dbConfig['host']),
      escapeshellarg(Config::$dbConfig['user']),
      escapeshellarg(Config::$dbConfig['pass']),
      escapeshellarg(Config::$dbConfig['dbname']),
      escapeshellarg($file)
    );

    shell_exec($cmd);

    if (!file_exists($file)) {
      throw new \RuntimeException('Errore durante export schema');
    }

    return basename($file);
  }
}
