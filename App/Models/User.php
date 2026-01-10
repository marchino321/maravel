<?php

namespace App\Models;

use PDO;
use Core\Model;
use Core\Classi\Flash;
use Core\Classi\Token;
use Core\Classi\Chiamate;
use App\Config;
use App\Debug;

if (!defined('CLI_MODE')) {
  defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}

/**
 * Class User
 *
 * Gestione utenti:
 * - Login sicuro
 * - Verifica password hash
 * - Sessione protetta
 * - Cookie remember-me
 * - Logging eventi
 *
 * Compatibile PHP 8.4+
 * Baseline FamilyNest
 *
 * @author Marco Dattisi
 */

class User extends Model
{
  // === Colonne DB (usate dal login) ===
  public int $idUtenteAutoIncrement;
  public string $nomeUtente;
  public string $cognomeUtente;
  public string $passwordUtente;
  public string $emailUtente;
  public int $permessiUtente;
  public string $uuidUser;
  public string $cellulareRecapitoUtente;
  public string $registrazioneUtente;
  public string $ultimoLogin;
  public int $eliminatoUtente;
  public string $ultimoCambioPassword;
  public string $avatar_utente;
  public string $DefaultSetting;
  // Permessi
  public int $idPermessoAutoIncrement;
  public string $descrizionePermesso;
  public string $permessiEffettivi;
  public int $eliminatoPermesso;
  // === Input runtime ===
  public string $email = '';
  public string $password = '';

  public array $ritorno = [];

  /**
   * Costruttore
   *
   * @param array    $data Dati iniziali (es. da form o fetch)
   * @param PDO|null $pdo  Connessione DB opzionale
   */
  public function __construct(array $data = [], ?PDO $pdo = null)
  {
    parent::__construct($pdo);

    foreach ($data as $key => $value) {
      // assegna solo se la proprietà esiste o è dinamica consentita
      $this->{$key} = $value;
    }
  }

  /**
   * Effettua login utente
   *
   * @return bool TRUE se login riuscito
   */
  public function loginUser(): bool
  {
    // 1️⃣ Recupero utente
    $user = $this->loginControl();

    if ($user === false) {
      Debug::log("Tentativo login fallito per {$this->email} - utente non trovato", 'AUTH');
      return false;
    }

    // 2️⃣ Verifica password
    if (!$this->checkPassword($user->passwordUtente)) {
      Debug::log("Tentativo login fallito per {$this->email} - password errata", 'AUTH');
      return false;
    }

    // 3️⃣ Session hardening
    session_regenerate_id(true);

    $userArray = get_object_vars($user);

    // Chiave principale sessione
    $_SESSION[Config::$SESSION_USER_KEY] = $userArray['idUtenteAutoIncrement'] ?? null;

    // Popola sessione evitando oggetti
    foreach ($userArray as $key => $value) {
      if ($key !== 'passwordUtente' && !is_object($value)) {
        $_SESSION[$key] = $value;
      }
    }

    // 4️⃣ Remember-me
    $token = $this->rememberLogin((int)$user->idUtenteAutoIncrement);
    if ($token !== false) {
      setcookie(
        'logedIn',
        $token,
        [
          'expires'  => time() + 60 * 60 * 24 * 30,
          'path'     => '/',
          'secure'   => !empty($_SERVER['HTTPS']),
          'httponly' => true,
          'samesite' => 'Strict',
        ]
      );
    }

    // 5️⃣ Aggiorna ultimo login
    $this->updateLastLogin((int)$user->idUtenteAutoIncrement);

    Debug::log("Login riuscito per {$this->email}", 'AUTH');

    return true;
  }

  /**
   * Verifica password hashata
   *
   * @param string $hash Hash salvato nel DB
   * @return bool
   */
  public function checkPassword(string $hash): bool
  {
    if (password_verify($this->password, $hash)) {
      return true;
    }

    Flash::AddMex('Password errata', Flash::DANGER, 'Login');
    Debug::log("Password errata per {$this->email}", 'AUTH');

    return false;
  }

  /**
   * Recupera utente tramite email
   *
   * @return self|false
   */
  public function loginControl(): self|false
  {
    $sql = 'SELECT * FROM tbl_utenti
            INNER JOIN ap_permessi
              ON tbl_utenti.permessiUtente = ap_permessi.idPermessoAutoIncrement
            WHERE emailUtente = :email
              AND tbl_utenti.eliminatoUtente = 0';

    $db = $this->getConnection();
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':email', $this->email, PDO::PARAM_STR);
    $stmt->execute();

    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
      Flash::AddMex(
        'Controlla di aver confermato la tua e-mail',
        Flash::INFO,
        'Login'
      );

      \App\Debug::log(
        "Login fallito per {$this->email} - email non confermata o utente eliminato",
        "SISTEMA"
      );

      return false;
    }

    return new self($data, $db);
  }

  /**
   * Genera token remember-me e lo salva su DB
   *
   * @param int $userId
   * @return string|false
   */
  public function rememberLogin(int $userId): string|false
  {
    $token = new Token();
    $tokenHash = $token->getHash();

    if ($tokenHash === '') {
      return false;
    }

    $expiry = time() + 60 * 60 * 24 * 30;

    $c = new Chiamate();
    $c->salva('ap_ricordaLogin', [
      'token_hash'              => $tokenHash,
      'utenteIdAutoIncrement'   => $userId,
      'scadenza_at'             => date('Y-m-d H:i:s', $expiry),
    ]);

    Debug::log("Generato token remember-me per utente {$userId}", 'AUTH');

    return $tokenHash;
  }

  /**
   * Aggiorna data ultimo login
   *
   * @param int $userId
   * @return void
   */
  private function updateLastLogin(int $userId): void
  {
    $c = new Chiamate();
    $c->aggiorna(
      'tbl_utenti',
      ['ultimoLogin' => date('Y-m-d H:i:s')],
      'idUtenteAutoIncrement',
      $userId
    );

    Debug::log("Aggiornato ultimo login per utente {$userId}", 'AUTH');
  }
}
