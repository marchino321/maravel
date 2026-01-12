<?php

namespace Core\View;

class Page
{
  private static string $title = '';

  public static function setTitle(string $title): void
  {
    self::$title = $title;
  }

  public static function getTitle(): string
  {
    return self::$title;
  }
}
