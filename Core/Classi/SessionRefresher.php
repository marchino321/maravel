<?php

namespace Core\Classi;

use App\Config;
use PDO;
use App\Debug;
use Core\Classi\Chiamate;

if (!defined("CLI_MODE")) {
  defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}
class SessionRefresher
{

  /**
   * Aggiorna i dati della sessione corrente
   */
  public function refresh(): void
  {
    $userIdKey = Config::$SESSION_USER_KEY;

    if (!isset($_SESSION[$userIdKey])) {
      Debug::log("Nessuna sessione da aggiornare.", "SESSIONE");
      return;
    }

    $userId = $_SESSION[$userIdKey];

    $c = new Chiamate();
    $userData = $c->seleziona('tbl_utenti', 'idUtenteAutoIncrement', $userId);

    if (!empty($userData[0])) {
      $data = $userData[0];
      //var_export($_SESSION);
      // Aggiorna solo campi "sicuri" senza inserire PDO o oggetti
      $_SESSION['nomeUtente']   = $data['nomeUtente'] ?? $_SESSION['nomeUtente'];
      $_SESSION['cognomeUtente'] = $data['cognomeUtente'] ?? $_SESSION['cognomeUtente'];
      $_SESSION['emailUtente']  = $data['emailUtente'] ?? $_SESSION['emailUtente'];
      $_SESSION['permessiUtente'] = $data['permessiUtente'] ?? $_SESSION['permessiUtente'];
      $_SESSION['avatar_utente']  = $data['avatar_utente'] ?? $_SESSION['avatar_utente'];
      $_SESSION['DefaultSetting']  = $data['DefaultSetting'] ?? $_SESSION['DefaultSetting'];
      // $_SESSION['DefaultSetting']  = $data['DefaultSetting'] ?? $_SESSION['DefaultSetting'];


      Debug::log("Sessione utente {$userId} aggiornata con successo", "SESSIONE");
    } else {
      Debug::log("Impossibile aggiornare sessione: utente {$userId} non trovato", "SESSIONE");
    }
  }
}
