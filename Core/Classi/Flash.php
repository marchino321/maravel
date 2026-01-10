<?php

namespace Core\Classi;

use App\Config;
use Core\Lang;
use Core\View\TwigManager;

// if (!defined("CLI_MODE")) {
//   defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
// }

/**
 * Gestione messaggi flash
 * - Memorizza messaggi in sessione
 * - Fornisce tipi predefiniti (SUCCESS, DANGER, INFO, WARNING)
 * - Funzione Twig per stampare messaggi
 * - Hook per aggiungere messaggi da plugin o controller
 */
class Flash
{
  const SUCCESS = 'success';
  const DANGER  = 'error';
  const INFO    = 'info';
  const WARNING = 'warning';

  private static array $catalog = [
    'insert.ok' => [
      'type'  => self::SUCCESS,
      'title' => 'Inserimento',
      'body'  => 'insert.ok'
    ],
    'update.ok' => [
      'type'  => self::INFO,
      'title' => 'Modifica',
      'body'  => 'update.ok'
    ],
    'field.missing' => [
      'type'  => self::WARNING,
      'title' => 'Campo mancante',
      'body'  => 'field.missing'
    ],
    'no.mail' => [
      'type'  => self::WARNING,
      'title' => 'Email non valida',
      'body'  => 'no.mail'
    ],
    'system.error' => [
      'type'  => self::DANGER,
      'title' => 'Errore',
      'body'  => 'system.error'
    ],
    'db.crash' => [
      'type'  => self::DANGER,
      'title' => '💥 Database',
      'body'  => 'db.crash'
    ],
    'db.install' => [
      'type'  => self::SUCCESS,
      'title' => '💥 Database',
      'body'  => 'db.install'
    ],
  ];
  public static function AddMex(string $testo, string $type = self::DANGER, string $title = ''): void
  {
    if (!isset($_SESSION['flash_mess'])) {
      $_SESSION['flash_mess'] = [];
    }

    // Emoji + titolo coerenti
    [$emoji, $titolo] = match ($type) {
      self::SUCCESS => ['✅', $title ?: 'Perfetto!'],
      self::DANGER  => ['❌', $title ?: 'Errore!'],
      self::INFO    => ['ℹ️', $title ?: 'Info!'],
      self::WARNING => ['⚠️', $title ?: 'Attenzione!'],
      default       => ['💬', $title ?: 'Messaggio...']
    };

    $_SESSION['flash_mess'][] = [
      'body'  => $testo,
      'type'  => $type,                   // success / error / info / warning
      'title' => $emoji . ' ' . $titolo   // 👈 emoji inserita direttamente
    ];
  }
  public static function AddByKey(string $key): void
  {
    if (!isset(self::$catalog[$key])) {
      self::AddMex("Chiave messaggio non trovata: $key", self::WARNING, "⚠️ Sistema");
      return;
    }

    $msg = self::$catalog[$key];

    // 🔥 Traduzione QUI (runtime)
    $body = \Core\Lang::get($msg['body']);

    self::AddMex(
      $body,
      $msg['type'],
      $msg['title']
    );
  }

  public static function GetMex(): array
  {
    $messages = $_SESSION['flash_mess'] ?? [];
    unset($_SESSION['flash_mess']);
    return $messages;
  }
}
