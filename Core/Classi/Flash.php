<?php

namespace Core\Classi;

use App\Config;
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
      'body'  => 'Inserimento avvenuto con successo.'
    ],
    'update.ok' => [
      'type'  => self::INFO,
      'title' => 'Modifica',
      'body'  => 'Modifica completata correttamente.'
    ],
    'field.missing' => [
      'type'  => self::WARNING,
      'title' => 'Campo mancante',
      'body'  => 'Alcuni campi obbligatori non sono stati compilati.'
    ],
    'no.mail' => [
      'type'  => self::WARNING,
      'title' => 'Email non valida',
      'body'  => 'Inserisci una email valida'
    ],
    'system.error' => [
      'type'  => self::DANGER,
      'title' => 'Errore',
      'body'  => 'Si è verificato un errore imprevisto. Riprova più tardi.'
    ],
    'db.crash' => [
      'type'  => self::DANGER,
      'title' => '💥 Database',
      'body'  => 'Errore durante la connessione al database.'
    ],
    'db.install' => [
      'type'  => self::SUCCESS,
      'title' => '💥 Database',
      'body'  => 'Installazione avvenuta con successo!'
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
    self::AddMex($msg['body'], $msg['type'], $msg['title']);
  }

  public static function GetMex(): array
  {
    $messages = $_SESSION['flash_mess'] ?? [];
    unset($_SESSION['flash_mess']);
    return $messages;
  }
}
