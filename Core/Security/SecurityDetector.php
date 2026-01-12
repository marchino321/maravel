<?php


namespace Core\Security;

use App\Config;
use App\Debug;

final class SecurityDetector
{
  private const RULES = [
    // Boolean-based
    '/(\'|")\s*or\s+(\d+)\s*=\s*\2/i',

    // Time-based
    '/\b(sleep|benchmark)\s*\(/i',

    // UNION
    '/\bunion\b\s+\bselect\b/i',

    // Commenti SQL
    '/(--|#|\/\*)/',

    // Hex / encoded payload
    '/0x[0-9a-f]{6,}/i',

    // Suspicious logic
    '/\b(select|insert|update|delete)\b.*\bfrom\b/i'
  ];

  public static function analyze(string $value, string $source = 'input'): void
  {
    foreach (self::RULES as $rule) {
      if (preg_match($rule, $value)) {
        self::log($value, $rule, $source);
        break;
      }
    }
  }

  private static function log(string $value, string $rule, string $source): void
  {
    $risp = [
      'type'   => 'SECURITY_DETECTION',
      'source' => $source,
      'ip'     => $_SERVER['REMOTE_ADDR'] ?? 'CLI',
      'uri'    => $_SERVER['REQUEST_URI'] ?? 'CLI',
      'rule'   => $rule,
      'value'  => substr($value, 0, 500),
    ];
    $dir = Config::$logDir;

    if (!is_dir($dir)) {
      mkdir($dir, 0755, true);
    }

    $file = $dir . '/security.log';

    $line = json_encode([
      'time'   => date('Y-m-d H:i:s'),
      'ip'     => $_SERVER['REMOTE_ADDR'] ?? 'CLI',
      'uri'    => $_SERVER['REQUEST_URI'] ?? 'CLI',
      'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
      'value'  => $value
    ], JSON_UNESCAPED_SLASHES);

    file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    Debug::log(implode('. ', $risp), 'SECURITY');
  }
}
