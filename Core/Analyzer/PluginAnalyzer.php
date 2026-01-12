<?php

declare(strict_types=1);

namespace Core\Analyzer;

use App\Config;
use ZipArchive;

final class PluginAnalyzer
{
  private string $zipPath;
  private array $errors = [];
  private array $config = [];

  public function __construct(string $zipPath)
  {
    $this->zipPath = $zipPath;
  }

  /* =============================
   * ENTRY POINT
   * ============================= */
  public function analyze(): bool
  {
    return
      $this->checkZip()
      && $this->checkStructure()
      && $this->checkConfig()
      && $this->checkCompatibility()
      && $this->checkSecurity();
  }

  /* =============================
   * CHECK ZIP
   * ============================= */
  private function checkZip(): bool
  {
    $zip = new ZipArchive();
    if ($zip->open($this->zipPath) !== true) {
      $this->errors[] = 'File ZIP non valido';
      return false;
    }
    $zip->close();
    return true;
  }

  /* =============================
   * CHECK STRUTTURA
   * ============================= */
  private function checkStructure(): bool
  {
    $zip = new ZipArchive();
    $zip->open($this->zipPath);

    $rootDir = null;

    for ($i = 0; $i < $zip->numFiles; $i++) {
      $name = $zip->getNameIndex($i);

      if (str_contains($name, '/../')) {
        $this->errors[] = 'Tentativo di path traversal rilevato';
        return false;
      }

      if (!$rootDir && str_ends_with($name, '/')) {
        $rootDir = explode('/', $name)[0];
      }
    }

    if (!$rootDir) {
      $this->errors[] = 'Struttura ZIP non valida (root mancante)';
      return false;
    }

    if (!$zip->locateName("$rootDir/config.json")) {
      $this->errors[] = 'config.json mancante';
      return false;
    }

    $zip->close();
    return true;
  }

  /* =============================
   * CHECK CONFIG
   * ============================= */
  private function checkConfig(): bool
  {
    $zip = new ZipArchive();
    $zip->open($this->zipPath);

    $root = explode('/', $zip->getNameIndex(0))[0];
    $configRaw = $zip->getFromName("$root/config.json");

    if (!$configRaw) {
      $this->errors[] = 'Impossibile leggere config.json';
      return false;
    }

    $config = json_decode($configRaw, true);

    if (!is_array($config)) {
      $this->errors[] = 'config.json non valido';
      return false;
    }

    $required = [
      'name',
      'version',
      'namespace',
      'main',
      'class',
      'min_core'
    ];

    foreach ($required as $key) {
      if (empty($config[$key])) {
        $this->errors[] = "Campo obbligatorio mancante: {$key}";
      }
    }

    if ($this->errors) return false;

    $this->config = $config;
    $zip->close();
    return true;
  }

  /* =============================
   * CHECK COMPATIBILITÀ CORE
   * ============================= */
  public function checkCompatibility(): ?bool
  {
    $file = Config::$baseDir . '/ConfigFiles/update.json';

    if (!file_exists($file)) {
      return false;
    }

    $json = json_decode(file_get_contents($file), true);
    if (version_compare($json['core_version'], $this->config['min_core'], '<')) {
      $this->errors[] = "Tema richiede core >= {$this->config['min_core']}";
      return false;
    }
    return true;
  }

  /* =============================
   * CHECK SICUREZZA
   * ============================= */
  private function checkSecurity(): bool
  {
    $zip = new ZipArchive();
    $zip->open($this->zipPath);

    $blacklist = [
      'eval(',
      'shell_exec',
      'exec(',
      'passthru',
      'system(',
      'base64_decode('
    ];

    for ($i = 0; $i < $zip->numFiles; $i++) {
      $name = $zip->getNameIndex($i);

      if (!str_ends_with($name, '.php')) continue;

      $code = $zip->getFromIndex($i);

      foreach ($blacklist as $bad) {
        if (stripos($code, $bad) !== false) {
          $this->errors[] =
            "Codice potenzialmente pericoloso in {$name}";
          return false;
        }
      }
    }

    $zip->close();
    return true;
  }

  /* =============================
   * UTIL
   * ============================= */
  public function getErrors(): array
  {
    return $this->errors;
  }

  public function getConfig(): array
  {
    return $this->config;
  }
}
