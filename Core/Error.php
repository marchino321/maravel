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
      if ($error !== null) {
        $e = new \ErrorException(
          $error['message'],
          0,
          $error['type'],
          $error['file'],
          $error['line']
        );
        self::handle($e);
      }
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
    // Modalità DEBUG → mostra dettagli
    if (Config::$DEBUG_CONSOLE === true) {
      Debug::log("❌ Errore (DEBUG attivo): " . $e->getMessage(), 'ERROR');

      //http_response_code(500);
      echo "<pre style='background:#111;color:#fff;padding:10px;border-radius:5px'>";
      echo "🚨 " . get_class($e) . "\n\n";
      echo "Messaggio: " . $e->getMessage() . "\n";
      echo "File: " . $e->getFile() . "\n";
      echo "Linea: " . $e->getLine() . "\n";
      echo "Trace:\n" . $e->getTraceAsString();
      echo "</pre>";
    }
    // Modalità produzione → log + pagina Twig
    else {
      $logFile = Config::$logDir . '/error.log';
      $logMessage = "[" . date('Y-m-d H:i:s') . "] " .
        "Errore: " . $e->getMessage() .
        " in " . $e->getFile() . " alla linea " . $e->getLine() . "\n" .
        $e->getTraceAsString() . "\n";

      // Scrittura log
      error_log($logMessage, 3, $logFile);
      Debug::log("❌ Errore registrato in produzione: " . $e->getMessage(), 'ERROR');

      http_response_code(500);

      if (self::$twigManager) {
        echo self::$twigManager->getTwig()->render('Error/Errors.html', [
          'title' => 'Errore 500',
          'messaggio' => '💥 Errore interno',
          'risposta' => Config::$DEBUG_CONSOLE ? $e->getMessage() : '',
        ]);
      } else {
        echo "<h1>Errore 500</h1><p>💥 Errore interno</p>";
      }

      exit;
    }
  }
}
