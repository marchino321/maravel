<?php

namespace Core\Services;

use App\Config;

class CliCommandService
{
  public function runMigrate(): array
  {
    // BLOCCO DI SICUREZZA
    if (!defined('CLI_WEB_ALLOWED') || CLI_WEB_ALLOWED !== true) {
      return [
        'success' => false,
        'output'  => 'Esecuzione CLI da web disabilitata'
      ];
    }

    // comando FISSO (non modificabile da UI)
    $command = 'php ' . escapeshellarg(Config::$baseDir . '/cli.php') . ' migrate 2>&1';

    $output = shell_exec($command);

    return [
      'success' => true,
      'output'  => trim($output)
    ];
  }
}
