<?php

namespace Core\Services;

use App\Config;

class SystemInfoService
{
  public function getSystemInfo(): array
  {
    $dir = $_SERVER['DOCUMENT_ROOT'] ?? __DIR__;

    return [
      'Sistema_Operativo' => $this->getSimpleOS(),
      'Nome_Host'         => gethostname(),
      'PHP_Versione'      => PHP_VERSION,
      'Server_Software'   => $_SERVER['SERVER_SOFTWARE'] ?? 'N/D',
      'Architettura'      => PHP_INT_SIZE * 8 . '-bit',
      'IP_Server'         => $_SERVER['SERVER_ADDR'] ?? 'N/D',
      'Numero_CPU'        => $this->getCpu(),
      'RAM_Totale'        => $this->getRam('2'),
      'RAM_Usata'         => $this->getRam('3'),
      'RAM_Libera'        => $this->getRam('4'),
      'Disco_Totale'      => $this->formatGb(disk_total_space($dir)),
      'Disco_Usato'       => $this->formatGb(disk_total_space($dir) - disk_free_space($dir)),
      'Disco_Libero'      => $this->formatGb(disk_free_space($dir)),
    ];
  }

  public function isDebugEnabled(): bool
  {
    $configFile = Config::$configDir . '/config.local.json';
    if (!file_exists($configFile)) {
      return false;
    }

    $json = json_decode(file_get_contents($configFile), true);
    return !empty($json['DEBUG_CONSOLE']);
  }

  /* ================= LOG ================= */

  public function listLogs(): array
  {
    $files = array_diff(scandir(Config::$logDir), ['.', '..']);
    $logs = [];

    foreach ($files as $file) {
      $logs[] = [
        'NomeFile' => $file,
        'Link'     => '?file=' . urlencode($file),
        'Elimina'  => '?elimina=' . urlencode($file),
      ];
    }

    return $logs;
  }

  public function getLogPath(string $file): ?string
  {
    $file = basename($file);
    $path = realpath(Config::$logDir . $file);

    return ($path && str_starts_with($path, realpath(Config::$logDir)))
      ? $path
      : null;
  }

  public function deleteLog(string $file): bool
  {
    $path = $this->getLogPath($file);
    return $path ? unlink($path) : false;
  }

  /* ================= UTILS ================= */

  private function getCpu(): string
  {
    return function_exists('shell_exec')
      ? trim(shell_exec('nproc'))
      : 'N/D';
  }

  private function getRam(string $col): string
  {
    if (!function_exists('shell_exec')) {
      return 'N/D';
    }

    return trim(shell_exec("free -h | grep Mem | awk '{print \${$col}}'"));
  }

  private function formatGb(float $bytes): string
  {
    return round($bytes / 1073741824, 2) . ' GB';
  }

  private function getSimpleOS(): string
  {
    $full = php_uname();

    preg_match('/^(Linux\s+\S+)/i', $full, $matches);
    $osHost = $matches[1] ?? 'Linux';

    preg_match('/(Debian|Ubuntu|CentOS|Red\s?Hat|Fedora|Alpine)/i', $full, $distro);
    return trim($osHost . ' ' . ($distro[1] ?? ''));
  }
}
