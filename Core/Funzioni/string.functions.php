<?php

use App\Config;

if (!defined("CLI_MODE")) {
  defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}
/**
 * Converte una stringa in uno slug adatto per URL
 *
 * Esempio: "Ciao Mondo!" diventa "ciao-mondo"
 *
 * @param string $text La stringa da trasformare
 * @param string $divider Carattere usato come separatore nello slug
 * @return string Slug generato
 */
function slugify(string $text, string $divider = '-'): string
{
  // Mappa dei caratteri accentati verso equivalenti non accentati
  $replaceSet = [
    'à' => 'a',
    'á' => 'a',
    'ä' => 'a',
    'â' => 'a',
    'è' => 'e',
    'é' => 'e',
    'ë' => 'e',
    'ê' => 'e',
    'ì' => 'i',
    'í' => 'i',
    'ï' => 'i',
    'î' => 'i',
    'ò' => 'o',
    'ó' => 'o',
    'ö' => 'o',
    'ô' => 'o',
    'ù' => 'u',
    'ú' => 'u',
    'ü' => 'u',
    'û' => 'u',
    'ñ' => 'n',
    'ç' => 'c'
  ];

  // Sostituisci caratteri accentati
  $text = strtr($text, $replaceSet);

  // Sostituisce tutto ciò che non è lettera o numero con il divider
  $text = preg_replace('~[^\pL\d]+~u', $divider, $text);

  // Traslittera caratteri UTF-8 in ASCII
  $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

  // Rimuove eventuali caratteri non validi
  $text = preg_replace('~[^-\w]+~', '', $text);

  // Rimuove divider all'inizio e alla fine
  $text = trim($text, $divider);

  // Sostituisce divider duplicati con uno solo
  $text = preg_replace('~-+~', $divider, $text);

  // Converte in minuscolo
  $text = strtolower($text);

  // Se lo slug risultante è vuoto, ritorna un valore di default
  if (empty($text)) {
    return 'n-a';
  }

  return $text;
}


function randomPassword($qCaratteri)
{
  $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
  $pass = array(); //remember to declare $pass as an array
  $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
  for ($i = 0; $i < $qCaratteri; $i++) {
    $n = rand(0, $alphaLength);
    $pass[] = $alphabet[$n];
  }
  return implode($pass); //turn the array into a string
}



/**
 * Genera un UUID v4 casuale oppure basato su un seed opzionale.
 *
 * @param string|null $seed Stringa opzionale da usare per rendere il risultato deterministico.
 * @return string UUID v4 standard
 */
function generateUUIDv4(?string $seed = null): string
{
  if ($seed !== null) {
    // Se viene passato un seed, generiamo un hash MD5 da esso
    $hash = md5($seed, true); // binario
    $data = substr($hash, 0, 16); // Prendiamo solo 16 byte
  } else {
    $data = random_bytes(16); // 16 byte casuali
  }

  // Imposta la versione a 4
  $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
  // Imposta la variante RFC 4122
  $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

  // Formatta in stringa standard UUID
  return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// Esempi di utilizzo
//echo generateUUIDv4() . PHP_EOL; // UUID casuale
//echo generateUUIDv4('mio-seed') . PHP_EOL; // UUID basato sul seed



/**
 * Codifica una stringa in base64 url-safe
 *
 * Sostituisce i caratteri +, /, = con -, _, '' rispettivamente
 *
 * @param string $string La stringa da codificare
 * @return string La stringa codificata in formato URL-safe
 */
function url_codifica(string $string): string
{
  $data = base64_encode($string);
  $data = str_replace(['+', '/', '='], ['-', '_', ''], $data);
  return $data;
}

/**
 * Decodifica una stringa codificata con url_codifica
 *
 * Ripristina i caratteri +, /, = e decodifica dalla base64
 *
 * @param string $string La stringa codificata URL-safe
 * @return string La stringa originale
 */
function url_decodifica(string $string): string
{
  $data = str_replace(['-', '_'], ['+', '/'], $string);

  // Ripristina il padding base64
  $mod4 = strlen($data) % 4;
  if ($mod4) {
    $data .= substr('====', $mod4);
  }

  return base64_decode($data);
}



function isValidApiKey($key): bool
{
  // Qui puoi verificare la chiave nel database o file
  $validKeys = ['mia-chiave-super-segreta', 'mia-chiave-super-segreta', 'altra-chiave-segreta']; // esempio
  return in_array($key, $validKeys, true);
}
