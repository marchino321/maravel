<?php

namespace Core;

use App\Config;
use App\Debug;

final class Lang
{
  private static array $strings = [];
  private static array $fallbackStrings = [];
  private static string $lang = 'it';
  private static string $fallback = 'it';
  private static array $pluginPaths = [];
  private static array $availableLangs = ['it', 'en', 'es'];

  public static function registerPluginLangPath(string $path): void
  {
    if (is_dir($path)) {
      self::$pluginPaths[] = rtrim($path, '/');
      Debug::log('Registrato path lingua plugin: ' . $path, 'LANG');
    } else {
      Debug::log('Path lingua plugin NON valido: ' . $path, 'LANG');
    }
  }

  public static function registerLanguage(string $lang): void
  {
    if (!in_array($lang, self::$availableLangs, true)) {
      self::$availableLangs[] = $lang;
    }
  }

  public static function available(): array
  {
    return self::$availableLangs;
  }

  public static function set(string $lang): void
  {
    self::$lang = $lang ?: 'it';
    Debug::log('Inizializzazione lingua: ' . self::$lang, 'LANG');
    // 1️⃣ carica core
    self::$strings = self::loadLangFile(Config::$baseDir . '/App/Lang', self::$lang);

    Debug::log('Caricate stringhe core per lingua: ' . self::$lang . ' (' . count(self::$strings) . ' chiavi)', 'LANG');

    // 2️⃣ carica plugin (sovrascrivono il core)
    foreach (self::$pluginPaths as $pluginLangDir) {
      $pluginStrings = self::loadLangFile($pluginLangDir, self::$lang);
      Debug::log('Caricate ' . count($pluginStrings) . ' stringhe plugin da ' . $pluginLangDir, 'LANG');


      self::$strings = array_replace(self::$strings, $pluginStrings);
    }
    Debug::log('Lingua richiesta = ' . $lang, 'LANG');
    // fallback IT
    self::$fallbackStrings = self::loadLangFile(Config::$baseDir . '/App/Lang', self::$fallback);

    Debug::log('Fallback lingua caricato: ' . self::$fallback . ' (' . count(self::$fallbackStrings) . ' chiavi)', 'LANG');
  }

  private static function loadLangFile(string $baseDir, string $lang): array
  {
    $file = "{$baseDir}/{$lang}.php";
    if (!file_exists($file)) {
      return [];
    }

    $data = require $file;
    return is_array($data) ? $data : [];
  }

  public static function get(string $key, array $params = []): string
  {
    $text = self::$strings[$key]
      ?? self::$fallbackStrings[$key]
      ?? $key;
    if ($text === null) {
      Debug::log('Chiave traduzione mancante: ' . $key . ' (lang=' . self::$lang . ')', 'LANG');
    }



    foreach ($params as $k => $v) {
      $text = str_replace('{' . $k . '}', (string)$v, $text);
    }

    return $text;
  }

  public static function current(): string
  {
    return self::$lang;
  }

  public static function detectBrowserLang(array $supported = ['it', 'en', 'es']): string
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
