<?php

namespace App\Console;

use App\Console\Commands\MigrateCommand;
use App\Console\Commands\RollbackCommand;

class Kernel
{
  protected array $commands = [];

  public function __construct()
  {
    $this->register(new MigrateCommand());
    $this->register(new RollbackCommand());
  }

  protected function register($command): void
  {
    $this->commands[$command->getName()] = $command;
  }

  public function handle(array $argv): void
  {
    $commandName = $argv[1] ?? null;

    if (!$commandName) {
      $this->listCommands();
      exit(1);
    }

    if (!isset($this->commands[$commandName])) {
      echo "❌ Comando non riconosciuto: {$commandName}\n\n";
      $this->listCommands();
      exit(1);
    }

    $this->commands[$commandName]->run(array_slice($argv, 2));
  }

  protected function listCommands(): void
  {
    echo "📌 Comandi disponibili:\n";
    foreach ($this->commands as $name => $cmd) {
      echo " - {$name}\n";
    }
  }
}
