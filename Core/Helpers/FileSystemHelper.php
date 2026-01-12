<?php

declare(strict_types=1);

namespace Core\Helpers;

use RuntimeException;

final class FileSystemHelper
{
  /**
   * Cancella una directory in modo ricorsivo (SAFE).
   * - Non usa exec
   * - Non segue symlink
   */
  public static function deleteDirectory(string $dir): void
  {
    if ($dir === '' || $dir === '/' || $dir === '.' || $dir === '..') {
      throw new RuntimeException('Directory non valida');
    }

    if (!is_dir($dir)) {
      return;
    }

    // Normalizza path (se possibile)
    $real = realpath($dir);
    if ($real === false) {
      // se non risolve, prova comunque
      $real = $dir;
    }

    $items = scandir($real);
    if ($items === false) {
      throw new RuntimeException("Impossibile leggere directory: {$real}");
    }

    foreach ($items as $item) {
      if ($item === '.' || $item === '..') continue;

      $path = $real . DIRECTORY_SEPARATOR . $item;

      // 🔒 non seguire symlink (evita cancellazioni fuori scope)
      if (is_link($path)) {
        // se vuoi, puoi anche solo unlinkare il link
        unlink($path);
        continue;
      }

      if (is_dir($path)) {
        self::deleteDirectory($path);
      } else {
        if (file_exists($path) && !unlink($path)) {
          throw new RuntimeException("Impossibile cancellare file: {$path}");
        }
      }
    }

    if (!rmdir($real)) {
      throw new RuntimeException("Impossibile cancellare directory: {$real}");
    }
  }

  /**
   * Verifica che un path stia DENTRO una base directory (anti traversal).
   */
  public static function assertInsideBaseDir(string $path, string $baseDir): void
  {
    $realPath = realpath($path);
    $realBase = realpath($baseDir);

    if ($realPath === false || $realBase === false) {
      throw new RuntimeException('Path non valido');
    }

    $realBase = rtrim($realBase, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    if (strpos($realPath, $realBase) !== 0) {
      throw new RuntimeException('Path fuori dalla directory consentita');
    }
  }
}
