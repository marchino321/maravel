<?php

namespace Core;

use App\Config;
use App\Debug;
use ZipArchive;


if (!defined("CLI_MODE")) {
  defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}
/**
 * Gestore aggiornamenti core
 *
 * - Controlla aggiornamenti dal server remoto
 * - Scarica e applica update tramite file singoli o pacchetti ZIP
 * - Verifica integrità con SHA256
 * - Esegue backup automatico con rollback
 * - Usa lock file per evitare aggiornamenti multipli simultanei
 */
class UpdateManager
{
  private string $versionFile;
  private string $updateServer;
  private string $backupDir;
  private string $lockFile;

  public function __construct()
  {
    $this->versionFile  = Config::$configDir . '/update.json';
    $this->updateServer = 'https://' . Config::$LinkUpdate . "/MyFiles/core.json";
    $this->backupDir    = Config::$baseDir . '/../backups/';
    $this->lockFile     = Config::$baseDir . '/../.update_lock';
  }

  /**
   * Controlla se sono disponibili aggiornamenti
   */
  public function checkForUpdates(): array
  {
    $local  = @json_decode(@file_get_contents($this->versionFile), true);
    $remote = @json_decode(@file_get_contents($this->updateServer), true);

    if (!is_array($local)) {
      return ['error' => "File update.json non valido o mancante"];
    }
    if (!is_array($remote)) {
      return ['error' => "File core.json non valido o mancante"];
    }

    $result = [
      'current'          => $local['core_version'] ?? 'unknown',
      'update_available' => false,
    ];

    if (isset($remote['latest_core']) && version_compare($remote['latest_core'], $local['core_version'], '>')) {
      $result['update_available'] = true;
      $result['latest']           = $remote['latest_core'];
      $result['patches']          = $remote['security_patches'] ?? [];
      $result['files']            = $remote['files'] ?? [];
      $result['zip']              = $remote['zip'] ?? null;
    }

    return $result;
  }

  /**
   * Applica aggiornamento
   */
  public function applyUpdate(): bool
  {
    if ($this->isLocked()) {
      Debug::log("🚫 Aggiornamento già in corso!", 'UPDATE');
      return false;
    }
    $this->lock();

    $updates = $this->checkForUpdates();

    if (isset($updates['error'])) {
      Debug::log("❌ Errore update: " . $updates['error'], 'UPDATE');
      $this->unlock();
      return false;
    }

    if (!$updates['update_available']) {
      Debug::log("✅ Nessun aggiornamento disponibile", 'UPDATE');
      $this->unlock();
      return false;
    }

    $ok = false;

    // 🔹 ZIP update
    if (!empty($updates['zip'])) {
      $ok = $this->applyZipUpdate($updates['zip'], $updates['latest']);
    } else {
      // 🔹 Aggiornamento file singoli
      $ok = $this->applyFilesUpdate($updates['files'], $updates['latest']);
    }

    $this->unlock();
    return $ok;
  }

  /**
   * Applica aggiornamento tramite file singoli
   */
  private function applyFilesUpdate(array $files, string $latestVersion): bool
  {
    $backupPath = $this->createBackup();

    foreach ($files as $file) {
      $url   = $file['url'] ?? null;
      $path  = $file['path'] ?? null;
      $hash  = $file['sha256'] ?? null;

      if (!$url || !$path) {
        Debug::log("⚠️ File update non valido (manca url o path)", 'UPDATE');
        continue;
      }

      $content = @file_get_contents($url);
      if ($content === false) {
        Debug::log("⚠️ Impossibile scaricare file: {$url}", 'UPDATE');
        continue;
      }

      if ($hash && !hash_equals(strtolower($hash), hash('sha256', $content))) {
        Debug::log("❌ Hash non valido per {$url}", 'UPDATE');
        continue;
      }

      $target = $_SERVER['DOCUMENT_ROOT'] . '/' . $path;
      $this->backupFile($target, $backupPath);

      file_put_contents($target, $content);
      Debug::log("🔄 Aggiornato file: {$path}", 'UPDATE');
    }

    $this->updateLocalVersion($latestVersion);
    return true;
  }

  /**
   * Applica aggiornamento tramite pacchetto ZIP
   */
  private function applyZipUpdate(array $zipInfo, string $latestVersion): bool
  {
    $zipUrl = $zipInfo['url'] ?? null;
    $hash   = $zipInfo['sha256'] ?? null;

    if (!$zipUrl) {
      Debug::log("❌ Nessun URL ZIP trovato", 'UPDATE');
      return false;
    }

    $tmpFile = sys_get_temp_dir() . '/update_' . time() . '.zip';
    $content = @file_get_contents($zipUrl);

    if ($content === false) {
      Debug::log("❌ Impossibile scaricare pacchetto ZIP: {$zipUrl}", 'UPDATE');
      return false;
    }

    if ($hash && !hash_equals(strtolower($hash), hash('sha256', $content))) {
      @unlink($tmpFile);
      Debug::log("❌ Hash ZIP non valido! File eliminato", 'UPDATE');
      return false;
    }

    file_put_contents($tmpFile, $content);

    $zip = new ZipArchive;
    if ($zip->open($tmpFile) === true) {
      $extractPath = Config::$baseDir . '/../update_tmp';
      if (!is_dir($extractPath)) mkdir($extractPath, 0777, true);

      $zip->extractTo($extractPath);
      $zip->close();
      @unlink($tmpFile);

      // Backup completo
      $backupPath = $this->createBackup();

      // Copia i file
      $this->copyRecursive($extractPath, $_SERVER['DOCUMENT_ROOT'], $backupPath);
      Debug::log("📦 Pacchetto ZIP applicato correttamente", 'UPDATE');

      // Pulisce cartella temporanea
      $this->removeDir($extractPath);

      $this->updateLocalVersion($latestVersion);
      return true;
    } else {
      Debug::log("❌ Errore apertura pacchetto ZIP", 'UPDATE');
      return false;
    }
  }

  /**
   * Copia ricorsiva con backup
   */
  private function copyRecursive(string $src, string $dst, string $backupPath): void
  {
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
      $target = $dst . '/' . $iterator->getSubPathName();

      if ($item->isDir()) {
        if (!is_dir($target)) mkdir($target, 0777, true);
      } else {
        $this->backupFile($target, $backupPath);
        copy($item, $target);
        Debug::log("🔄 Copiato file: {$target}", 'UPDATE');
      }
    }
  }

  /**
   * Backup singolo file
   */
  private function backupFile(string $target, string $backupPath): void
  {
    if (file_exists($target)) {
      $dest = $backupPath . '/' . ltrim(str_replace($_SERVER['DOCUMENT_ROOT'], '', $target), '/');
      if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0777, true);
      copy($target, $dest);
      Debug::log("💾 Backup creato per {$target}", 'UPDATE');
    }
  }

  /**
   * Crea backup completo (directory timestampata)
   */
  private function createBackup(): string
  {
    $path = $this->backupDir . date('Ymd_His');
    mkdir($path, 0777, true);

    if (file_exists($this->versionFile)) {
      copy($this->versionFile, $path . '/update.json');
    }

    Debug::log("💾 Backup creato in {$path}", 'UPDATE');
    return $path;
  }

  /**
   * Aggiorna il file update.json locale
   */
  private function updateLocalVersion(string $latest): void
  {
    $local = json_decode(file_get_contents($this->versionFile), true);
    $local['core_version'] = $latest;
    $local['last_update']  = date('Y-m-d H:i:s');
    file_put_contents($this->versionFile, json_encode($local, JSON_PRETTY_PRINT));
    Debug::log("✅ Versione locale aggiornata a {$latest}", 'UPDATE');
  }

  /**
   * Lock/unlock aggiornamento
   */
  private function lock(): void
  {
    file_put_contents($this->lockFile, (string)getmypid());
  }

  private function unlock(): void
  {
    if (file_exists($this->lockFile)) unlink($this->lockFile);
  }

  private function isLocked(): bool
  {
    return file_exists($this->lockFile);
  }

  /**
   * Rollback all'ultimo backup
   */
  public function rollback(): bool
  {
    $backups = glob($this->backupDir . '*', GLOB_ONLYDIR);
    if (empty($backups)) {
      Debug::log("⚠️ Nessun backup trovato per rollback", 'UPDATE');
      return false;
    }
    rsort($backups);
    $lastBackup = $backups[0];

    $this->copyRecursive($lastBackup, $_SERVER['DOCUMENT_ROOT'], $this->backupDir . 'rollback_' . time());

    $backupUpdateJson = $lastBackup . '/update.json';
    if (file_exists($backupUpdateJson)) {
      copy($backupUpdateJson, $this->versionFile);
      Debug::log("🔄 update.json ripristinato", 'UPDATE');
    }

    Debug::log("🔙 Rollback eseguito da {$lastBackup}", 'UPDATE');
    return true;
  }

  /**
   * Cancella ricorsivamente una directory
   */
  private function removeDir(string $dir): void
  {
    if (!is_dir($dir)) return;
    $it = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
    $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
      $file->isDir() ? rmdir($file) : unlink($file);
    }
    rmdir($dir);
    Debug::log("🧹 Rimossa cartella temporanea: {$dir}", 'UPDATE');
  }
}
