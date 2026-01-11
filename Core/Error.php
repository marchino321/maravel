<?php

declare(strict_types=1);

namespace Core;

use App\Config;
use App\Debug;
use Core\View\TwigManager;

if (!defined("CLI_MODE")) {
  defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}

/**
 * Gestione centralizzata di errori ed eccezioni.
 *
 * - Se DEBUG_CONSOLE è true → mostra dettagli completi a schermo.
 * - Se DEBUG_CONSOLE è false → scrive su log e mostra pagina Twig di errore.
 */
class Error
{
  private static ?TwigManager $twigManager = null;

  /**
   * Inizializza i gestori globali di errori ed eccezioni.
   *
   * @param TwigManager|null $twigManager Per renderizzare errori con Twig
   */
  public static function init(?TwigManager $twigManager = null): void
  {
    self::$twigManager = $twigManager;

    // Gestore globale per eccezioni
    set_exception_handler([self::class, 'handle']);

    // Trasforma errori PHP in eccezioni (eccetto quelli silenziati con @)
    set_error_handler(function (int $severity, string $message, string $file, int $line): void {
      if (!(error_reporting() & $severity)) {
        return;
      }
      throw new \ErrorException($message, 0, $severity, $file, $line);
    });

    // Gestione shutdown per errori fatali
    register_shutdown_function(function (): void {
      $error = error_get_last();

      if ($error === null) {
        return;
      }

      if (!in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
      }

      $e = new \ErrorException(
        $error['message'],
        0,
        $error['type'],
        $error['file'],
        $error['line']
      );

      self::handle($e);
    });

    Debug::log("🛠 Error handler inizializzato", 'ERROR');
  }

  /**
   * Gestore principale per eccezioni e errori convertiti in Throwable.
   *
   * @param \Throwable $e L'errore o eccezione da gestire
   */
  public static function handle(\Throwable $e): void
  {
    http_response_code(500);

    if (Config::$DEBUG_CONSOLE === true) {

      Debug::log("❌ Errore (DEBUG attivo): " . $e->getMessage(), 'ERROR');

      $exceptionClass = get_class($e);
      $message = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
      $file    = $e->getFile();
      $line    = $e->getLine();
      $trace   = htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8');

      echo <<<HTML
<style>
  body{background:#2e3b54;margin:0}
  .mf-error-box{
    background:#0e1117;color:#e6edf3;
    padding:20px;margin:20px;
    border-radius:8px;
    font-family:ui-monospace,monospace;
    box-shadow:0 0 0 1px #30363d
  }
  .mf-error-title{font-size:18px;color:#ff6b6b;margin-bottom:12px}
  .mf-error-meta{font-size:13px;color:#9da7b1}
  .mf-error-meta span{display:block}
  .mf-error-trace{
    background:#161b22;padding:10px;border-radius:6px;
    font-size:12px;max-height:300px;overflow:auto;white-space:pre-wrap
  }
</style>

<div class="mf-error-box">
  <div class="mf-error-title">🚨 Unhandled Exception</div>
  <div class="mf-error-meta">
    <span><b>Type:</b> {$exceptionClass}</span>
    <span><b>Message:</b> {$message}</span>
    <span><b>File:</b> {$file}</span>
    <span><b>Line:</b> {$line}</span>
  </div>
  <h4 style="color:#58a6ff">Stack trace</h4>
  <div class="mf-error-trace">{$trace}</div>
</div>
HTML;
      exit;
    }

    // PRODUZIONE
    Debug::log("❌ Errore produzione: " . $e->getMessage(), 'ERROR');

    if (self::$twigManager) {
      echo self::$twigManager->getTwig()->render('Error/Errors.html');
    } else {
      echo "<h1>Errore 500</h1>";
    }

    exit;
  }
}
