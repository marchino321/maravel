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
    $this->updateServer = "https://hd.marcodattisi.it/MyFiles/core.json";
    $systemDir = $_SERVER['DOCUMENT_ROOT'] . '/_system';

    if (!is_dir($systemDir)) {
      mkdir($systemDir, 0777, true);
      Debug::log("📁 Directory _system creata", 'UPDATE');
    }

    $this->backupDir = $systemDir . '/backups';
    $this->lockFile  = $systemDir . '/.update_lock';
  }

  /**
   * Normalizza un path e garantisce che resti dentro DOCUMENT_ROOT
   */
  private function safePath(string $path): string
  {
    $realBase = realpath($_SERVER['DOCUMENT_ROOT']);
    $realPath = realpath($path);

    if ($realPath === false) {
      return $_SERVER['DOCUMENT_ROOT'] . '/_system/' . basename($path);
    }

    // se il path esce da public_html → fallback automatico
    if ($realBase && !str_starts_with($realPath, $realBase)) {
      Debug::log("⚠️ Path fuori open_basedir, fallback applicato: {$path}", 'UPDATE');

      return $realBase . '/_system/' . basename($path);
    }

    return $path;
  }
  /**
   * Garantisce che la directory del file esista
   */
  private function ensureDirectory(string $filePath): void
  {
    $dir = is_dir($filePath) ? $filePath : dirname($filePath);

    if (!is_dir($dir)) {
      mkdir($dir, 0755, true);
      Debug::log("📁 Directory creata: {$dir}", 'UPDATE');
    }
  }

  /**
   * Controlla aggiornamenti
   */
  public function checkForUpdates(): array
  {
    if (!file_exists($this->versionFile)) {
      return ['error' => "File update.json mancante"];
    }

    $local  = json_decode(file_get_contents($this->versionFile), true);
    $remote = json_decode(@file_get_contents($this->updateServer), true);

    if (!is_array($local)) {
      return ['error' => "File update.json non valido"];
    }
    if (!is_array($remote)) {
      return ['error' => "File core.json remoto non valido"];
    }

    $result = [
      'current'          => $local['core_version'] ?? 'unknown',
      'update_available' => false,
    ];

    if (
      isset($remote['latest_core']) &&
      version_compare($remote['latest_core'], $local['core_version'], '>')
    ) {
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
      Debug::log("🚫 Aggiornamento già in corso", 'UPDATE');
      return false;
    }

    $this->lock();

    $updates = $this->checkForUpdates();

    if (isset($updates['error'])) {
      Debug::log("❌ Errore update: {$updates['error']}", 'UPDATE');
      $this->unlock();
      return false;
    }

    if (!$updates['update_available']) {
      Debug::log("✅ Nessun aggiornamento disponibile", 'UPDATE');
      $this->unlock();
      return false;
    }

    $ok = !empty($updates['zip'])
      ? $this->applyZipUpdate($updates['zip'], $updates['latest'])
      : $this->applyFilesUpdate($updates['files'], $updates['latest']);

    $this->unlock();
    return $ok;
  }

  /**
   * Aggiornamento file singoli
   */
  private function applyFilesUpdate(array $files, string $latestVersion): bool
  {
    $backupPath = $this->createBackup();

    foreach ($files as $file) {
      $url  = $file['url'] ?? null;
      $path = $file['path'] ?? null;
      $hash = $file['sha256'] ?? null;

      if (!$url || !$path) {
        Debug::log("⚠️ File update non valido", 'UPDATE');
        continue;
      }

      $content = @file_get_contents($url);
      if ($content === false) {
        Debug::log("⚠️ Download fallito: {$url}", 'UPDATE');
        continue;
      }

      if ($hash && !hash_equals(strtolower($hash), hash('sha256', $content))) {
        Debug::log("❌ Hash non valido: {$path}", 'UPDATE');
        continue;
      }

      $target = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($path, '/');

      // ✅ FIX 1: crea directory target
      $this->ensureDirectory($target);

      $this->backupFile($target, $backupPath);

      file_put_contents($target, $content);
      Debug::log("🔄 Aggiornato file: {$path}", 'UPDATE');
    }

    $this->updateLocalVersion($latestVersion);
    return true;
  }

  /**
   * Aggiornamento ZIP
   */
  private function applyZipUpdate(array $zipInfo, string $latestVersion): bool
  {
    $zipUrl = $zipInfo['url'] ?? null;
    $hash   = $zipInfo['sha256'] ?? null;

    if (!$zipUrl) {
      Debug::log("❌ URL ZIP mancante", 'UPDATE');
      return false;
    }

    $content = @file_get_contents($zipUrl);
    if ($content === false) {
      Debug::log("❌ Download ZIP fallito", 'UPDATE');
      return false;
    }

    if ($hash && !hash_equals(strtolower($hash), hash('sha256', $content))) {
      Debug::log("❌ Hash ZIP non valido", 'UPDATE');
      return false;
    }

    $tmpFile = sys_get_temp_dir() . '/update_' . time() . '.zip';
    file_put_contents($tmpFile, $content);

    $zip = new ZipArchive;
    if ($zip->open($tmpFile) !== true) {
      Debug::log("❌ Errore apertura ZIP", 'UPDATE');
      return false;
    }

    $extractPath = Config::$baseDir . '/../update_tmp';
    if (!is_dir($extractPath)) {
      mkdir($extractPath, 0777, true);
    }

    $zip->extractTo($extractPath);
    $zip->close();
    unlink($tmpFile);

    $backupPath = $this->createBackup();

    $this->copyRecursive($extractPath, $_SERVER['DOCUMENT_ROOT'], $backupPath);
    $this->removeDir($extractPath);

    $this->updateLocalVersion($latestVersion);
    Debug::log("📦 ZIP applicato correttamente", 'UPDATE');
    return true;
  }

  /**
   * Backup file singolo
   */
  private function backupFile(string $target, string $backupPath): void
  {
    if (!file_exists($target)) {
      return;
    }

    $dest = $backupPath . '/' . ltrim(
      str_replace($_SERVER['DOCUMENT_ROOT'], '', $target),
      '/'
    );

    if (!is_dir(dirname($dest))) {
      mkdir(dirname($dest), 0777, true);
    }

    copy($target, $dest);
    Debug::log("💾 Backup: {$dest}", 'UPDATE');
  }

  /**
   * Backup completo
   */
  private function createBackup(): string
  {
    // ✅ FIX 2: crea directory backup se manca
    $this->ensureDirectory($this->backupDir . '/dummy.txt');

    $path = $this->backupDir . date('Ymd_His');
    mkdir($path, 0777, true);

    if (file_exists($this->versionFile)) {
      copy($this->versionFile, $path . '/update.json');
    }

    Debug::log("💾 Backup creato in {$path}", 'UPDATE');
    return $path;
  }

  /**
   * Aggiorna versione locale
   */
  private function updateLocalVersion(string $latest): void
  {
    // ✅ FIX 3: directory config garantita
    $this->ensureDirectory($this->versionFile);

    $local = json_decode(file_get_contents($this->versionFile), true);
    $local['core_version'] = $latest;
    $local['last_update']  = date('Y-m-d H:i:s');

    file_put_contents(
      $this->versionFile,
      json_encode($local, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

    Debug::log("✅ Versione aggiornata a {$latest}", 'UPDATE');
  }

  /**
   * Lock update
   */
  private function lock(): void
  {
    file_put_contents($this->lockFile, (string) getmypid());
  }

  private function unlock(): void
  {
    if (file_exists($this->lockFile)) {
      unlink($this->lockFile);
    }
  }

  private function isLocked(): bool
  {
    return file_exists($this->lockFile);
  }

  /**
   * Rollback
   */
  public function rollback(): bool
  {
    $backups = glob($this->backupDir . '*', GLOB_ONLYDIR);
    if (!$backups) {
      Debug::log("⚠️ Nessun backup per rollback", 'UPDATE');
      return false;
    }

    rsort($backups);
    $lastBackup = $backups[0];

    $this->copyRecursive(
      $lastBackup,
      $_SERVER['DOCUMENT_ROOT'],
      $this->backupDir . 'rollback_' . time()
    );

    if (file_exists($lastBackup . '/update.json')) {
      copy($lastBackup . '/update.json', $this->versionFile);
    }

    Debug::log("🔙 Rollback da {$lastBackup}", 'UPDATE');
    return true;
  }

  /**
   * Cancella directory
   */
  private function removeDir(string $dir): void
  {
    if (!is_dir($dir)) return;

    $it = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($it as $file) {
      $file->isDir() ? rmdir($file) : unlink($file);
    }

    rmdir($dir);
  }

  /**
   * Copia ricorsiva
   */
  private function copyRecursive(string $src, string $dst, string $backupPath): void
  {
    $it = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($it as $item) {
      $target = $dst . '/' . $it->getSubPathName();

      if ($item->isDir()) {
        if (!is_dir($target)) mkdir($target, 0777, true);
      } else {
        $this->backupFile($target, $backupPath);
        copy($item, $target);
      }
    }
  }
}
