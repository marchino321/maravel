<?php

namespace App;

class Debug
{
  private static array $logs = [];

  private static array $tagMap = [
    'PLUGIN'     => ['label' => '[PLUGIN]',     'emoji' => '🔌', 'color' => '#4CAF50'],
    'CONTROLLER' => ['label' => '[CONTROLLER]', 'emoji' => '🎮', 'color' => '#2196F3'],
    'ROUTER'     => ['label' => '[ROUTER]',     'emoji' => '🛣️', 'color' => '#9C27B0'],
    'VIEW'       => ['label' => '[VIEW]',       'emoji' => '🖼️', 'color' => '#FF9800'],
    'APP'        => ['label' => '[CORE]',       'emoji' => '✅', 'color' => '#692cb4ff'],
    'DEFAULT'    => ['label' => '[DEBUG]',      'emoji' => '🐞', 'color' => '#9E9E9E'],
    'CONFIG'     => ['label' => '[CONFIG]',     'emoji' => '⭐', 'color' => '#FFC107'],
    'MODEL'      => ['label' => '[MODEL-DB]',   'emoji' => '💾', 'color' => '#E91E63'],
    'ERROR'      => ['label' => '[ERROR]',      'emoji' => '☢️', 'color' => '#F44336'],
    'TEMPLATE'   => ['label' => '[TEMPLATE]',   'emoji' => '✏️', 'color' => '#9c3c87ff'],
    'MENU'       => ['label' => '[MENU]',       'emoji' => '🔷', 'color' => '#36c5f4ff'],
    'AJAX'       => ['label' => '[AJAX]',       'emoji' => '🔥', 'color' => '#4da87fff'],
    'SECURITY'   => ['label' => '[SECURITY]',   'emoji' => '💀', 'color' => '#e92525ff'],
    'EVENTI'     => ['label' => '[EVENTI]',     'emoji' => '🎉', 'color' => '#27885eff'],
    'MYSQL-DB'   => ['label' => '[MYSQL-DB]',   'emoji' => '❤️', 'color' => '#62cb50ff'],
    'PERMESSI'   => ['label' => '[PERMESSI]',   'emoji' => '🔑', 'color' => '#f3a7e8ff'],
    'SESSIONE'   => ['label' => '[SESSIONE]',   'emoji' => '🪪', 'color' => '#fd7e14'],
    'LANG'       => ['label' => '[LANG]',       'emoji' => '🌍', 'color' => '#17a2b8'],
    'THEME'      => ['label' => '[THEME]',      'emoji' => '🎨', 'color' => '#6f42c1'],
  ];
  public static function getAndClearLogs(): array
  {
    $logs = self::$logs;
    self::$logs = []; // 🔹 svuota subito
    return $logs;
  }
  public static function log(string $message, string $tag = 'DEFAULT'): void
  {
    $tag = strtoupper($tag);
    $info = self::$tagMap[$tag] ?? self::$tagMap['DEFAULT'];

    self::$logs[] = [
      'message' => $message,
      'tag'     => $info['label'],
      'emoji'   => $info['emoji'],
      'color'   => $info['color'],
    ];
  }

  /**
   * Restituisce tutti i log come array
   */
  public static function getLogs(): array
  {
    $logS = self::$logs;
    self::$logs = []; // 🔹 svuota subito
    return $logS;
  }

  /**
   * Pannello HTML debug
   */
  public static function render(): string
  {
    if (empty(self::$logs)) {
      return '';
    }


    if (!Config::$DEBUG_CONSOLE) {
      return '';
    }

    $logs = json_encode(self::$logs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    self::$logs = [];
    return <<<HTML
<script>
(function(){
    const logs = {$logs};
    if (window.AjaxHelper && typeof AjaxHelper.printLogs === "function") {
        AjaxHelper.printLogs(logs);
    } else {
        logs.forEach(log => {
            console.log(
                `%c\${log.emoji} \${log.tag} \${log.message}`,
                `color:\${log.color}; font-weight:bold`
            );
        });
    }
})();
</script>
HTML;
  }
  public static function renderAjaxLogs(string $tag): array
  {
    if (empty(self::$logs) || !Config::$DEBUG_CONSOLE) {
      return [];
    }

    $filteredLogs = array_filter(self::$logs, function ($log) use ($tag) {
      return $log['tag'] === (self::$tagMap[$tag]['label'] ?? $tag);
    });

    return array_values($filteredLogs); // array pronto, ci pensa jsonResponse a encodarlo
  }
}
