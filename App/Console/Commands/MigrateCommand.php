<?php

namespace App\Console\Commands;

use App\Config;
use Core\Database;


class MigrateCommand extends \Core\Model
{
  public function getName(): string
  {
    return 'migrate';
  }

  public function run(array $args = []): void
  {
    echo "🚀 Avvio migrazioni...\n";

    $db = self::getDb();
    $path =  Config::$baseDir . '/migrations';

    $files = glob($path . '/*.php');
    sort($files);

    foreach ($files as $file) {
      $migration = require $file;

      if (!is_array($migration) || !isset($migration['up'])) {
        echo "⚠️ Migrazione non valida: {$file}\n";
        continue;
      }

      echo "➡️  Eseguo: " . basename($file) . "\n";
      $migration['up']($db);
    }

    echo "✅ Migrazioni completate.\n";
  }
}
