<?php

use App\Config;

if (!defined("CLI_MODE")) {
  defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}
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

function back()
{
  return;
}
