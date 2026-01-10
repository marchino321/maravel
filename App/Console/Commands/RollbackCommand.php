<?php

namespace App\Console\Commands;

use Core\Database;

class RollbackCommand
{
  public function getName(): string
  {
    return 'migrate:rollback';
  }

  public function run(array $args = []): void
  {
    echo "↩️ Rollback NON automatico (da definire log migrazioni)\n";
    echo "⚠️ Per ora rollback manuale per singola migrazione.\n";
  }
}
