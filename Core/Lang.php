<?php

namespace Core;

use App\Config;

final class Lang
{
  private static array $strings = [];
  private static array $fallbackStrings = [];
  private static string $lang = 'it';
  private static string $fallback = 'it';

  public static function set(string $lang): void
  {
    self::$lang = $lang ?: 'it';

    $file = Config::$baseDir . '/App/Lang/' . self::$lang . '.php';
    $loaded = [];

    if (file_exists($file)) {
      $loaded = require $file;
    }

    // ✅ se non è un array, non rompere l’app
    self::$strings = is_array($loaded) ? $loaded : [];

    // fallback IT
    $fallbackFile = Config::$baseDir . '/App/Lang/it.php';
    $fallbackLoaded = [];

    if (file_exists($fallbackFile)) {
      $fallbackLoaded = require $fallbackFile;
    }

    self::$fallbackStrings = is_array($fallbackLoaded) ? $fallbackLoaded : [];
  }

  public static function get(string $key, array $params = []): string
  {
    $text = self::$strings[$key]
      ?? self::$fallbackStrings[$key]
      ?? $key;

    // Placeholder: {name}, {count}, ecc.
    if ($params) {
      foreach ($params as $k => $v) {
        $text = str_replace('{' . $k . '}', (string)$v, $text);
      }
    }

    return $text;
  }

  public static function current(): string
  {
    return self::$lang;
  }

  public static function detectBrowserLang(array $supported = ['it', 'en', 'fr', 'es']): string
  {
    if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
      return 'it';
    }

    foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $lang) {
      $code = strtolower(substr(trim($lang), 0, 2));
      if (in_array($code, $supported, true)) {
        return $code;
      }
    }

    return 'it';
  }
}
