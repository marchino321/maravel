<?php

namespace App\Console\Commands;



class CronBackupCommand
{
  public function getName(): string
  {
    return 'cron:backup';
  }

  public function run(array $args = []): void
  {
    (new BackupController())->eseguiBackup();
  }
}
