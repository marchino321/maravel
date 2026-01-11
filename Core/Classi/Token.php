<?php

namespace Core\Classi;

use App\Config;


/**
 * Classe Token
 *
 * Genera e gestisce token sicuri, con possibilità di hash HMAC.
 * 
 * PHP version 8.3
 */
class Token
{
  /**
   * Valore del token
   *
   * @var string
   */
  protected string $token;
  private string $salt;



  /**
   * Costruttore
   *
   * Se viene passato un valore, lo utilizza come token,
   * altrimenti genera un token casuale sicuro di 32 caratteri esadecimali.
   *
   * @param string|null $token_value Token opzionale
   */
  public function __construct(?string $token_value = null)
  {
    if ($token_value !== null) {
      $this->token = $token_value;
    } else {
      $this->token = bin2hex(random_bytes(16)); // 16 byte = 32 caratteri esadecimali
      // Genera un salt casuale di 32 byte (64 caratteri esadecimali)
      $this->salt = bin2hex(random_bytes(32));
    }
  }

  /**
   * Restituisce il valore del token
   *
   * @return string
   */
  public function getValue(): string
  {
    return $this->token;
  }
  public function getSalt(): string
  {
    return $this->salt;
  }
  /**
   * Restituisce l'hash HMAC del token usando il SALT definito in Config
   *
   * @return string
   */
  public function getHash(): string
  {
    return hash_hmac('sha256', $this->token, $this->getSalt());
  }
}
