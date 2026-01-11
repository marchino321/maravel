<?php

namespace Core\Helpers;

final class LangHelper
{
  public static function flag(string $lang): string
  {
    return match ($lang) {
      'it' => '🇮🇹',
      'en' => '🇬🇧',
      'es' => '🇪🇸',
      'ru' => '🇷🇺',
      'fr' => '🇫🇷',
      'de' => '🇩🇪',
      default => '🌐',
    };
  }

  public static function label(string $lang): string
  {
    return match ($lang) {
      'it' => 'Italiano',
      'en' => 'English',
      'es' => 'Español',
      'ru' => 'Русский',
      'fr' => 'Français',
      'de' => 'Deutsch',
      default => strtoupper($lang),
    };
  }
}
