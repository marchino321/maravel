<?php

namespace Core;

use App\Config;
use App\Debug;
use ZipArchive;


if (!defined("CLI_MODE")) {
  defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}
class BackupManager
{
  private string $backupDir;

  public function __construct()
  {
    $this->backupDir = Config::$baseDir . '/backups';
    if (!is_dir($this->backupDir)) {
      mkdir($this->backupDir, 0777, true);
    }
  }

  /**
   * Esporta il progetto in un file ZIP
   *
   * @param bool $withDb   Se true aggiunge il dump SQL del database
   * @param array $tables  Tabelle da includere nel dump (se vuoto → tutte)
   * @return string Percorso del file ZIP creato
   */
  public function exportProject(bool $withDb = false, array $tables = []): string
  {
    $filename = "backup_" . date("Ymd_His") . ".zip";
    $zipPath  = $this->backupDir . '/' . $filename;

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
      throw new \RuntimeException("Impossibile creare ZIP: $zipPath");
    }

    // 🔹 Aggiunge tutti i file del progetto
    $root = Config::$baseDir . '/';
    $this->addFolderToZip($root, $zip, $root);

    // 🔹 Aggiunge dump DB
    if ($withDb) {
      $dump = $this->dumpDatabase($tables);
      $zip->addFromString("database.sql", $dump);
    }

    $zip->close();

    Debug::log("📦 Creato backup progetto: {$filename}", "BACKUP");
    return "/backups/$filename";
  }

  /**
   * Aggiunge cartelle e file al pacchetto ZIP
   */
  private function addFolderToZip(string $folder, ZipArchive $zip, string $base): void
  {
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($folder, \FilesystemIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
      $filePath  = $file->getPathname();

      // 🔧 correzione: non tagliare la prima lettera
      $localPath = ltrim(substr($filePath, strlen($base)), '/\\');

      if ($file->isDir()) continue; // salta cartelle
      if (str_contains($filePath, "/backups/")) continue; // non includere i vecchi backup

      $zip->addFile($filePath, $localPath);
    }
  }

  /**
   * Esegue un dump SQL del database
   *
   * @param array $tables Tabelle da includere (se vuoto → tutte)
   * @return string SQL generato
   */
  private function dumpDatabase(array $tables = []): string
  {
    $mysqli = new \mysqli(
      Config::$dbConfig['host'],
      Config::$dbConfig['user'],
      Config::$dbConfig['pass'],
      Config::$dbConfig['dbname']
    );

    if ($mysqli->connect_error) {
      throw new \RuntimeException("Errore connessione DB: " . $mysqli->connect_error);
    }

    $dump = "-- Database dump generato " . date('Y-m-d H:i:s') . "\n\n";
    $tableRes = $mysqli->query("SHOW TABLES");
    $allTables = [];
    while ($row = $tableRes->fetch_array()) {
      $allTables[] = $row[0];
    }

    foreach ($allTables as $table) {
      // 🔹 esporta SEMPRE la struttura
      $res = $mysqli->query("SHOW CREATE TABLE `$table`");
      $row = $res->fetch_assoc();
      $dump .= $row['Create Table'] . ";\n\n";

      // 🔹 se la tabella è inclusa → esporta ANCHE i record
      if (empty($tables) || in_array($table, $tables)) {
        $res = $mysqli->query("SELECT * FROM `$table`");
        while ($data = $res->fetch_assoc()) {
          $cols = array_map(fn($v) => "`$v`", array_keys($data));
          $vals = array_map(fn($v) => is_null($v) ? "NULL" : "'" . $mysqli->real_escape_string($v) . "'", array_values($data));
          $dump .= "INSERT INTO `$table` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ");\n";
        }
      }

      $dump .= "\n\n";
    }

    return $dump;
  }
}
