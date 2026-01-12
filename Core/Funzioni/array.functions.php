<?php

declare(strict_types=1);

use App\Config;

if (!defined('CLI_MODE')) {
  defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}
/**
 * =========================================
 * ARRAY FUNCTIONS – CORE
 * =========================================
 * Funzioni di utilità per la gestione array
 * - PURE FUNCTIONS
 * - NO HTML
 * - NO THEME
 * - NO SESSION
 * =========================================
 */



/**
 * Rimuove ricorsivamente chiavi indesiderate da un array.
 *
 * Utile per:
 * - pulizia dati DB
 * - export API
 * - confronto strutture
 *
 * @param array $array
 * @param array|null $keysToRemove Se null usa preset interno
 * @return array
 */
function array_remove_keys_recursive(
  array $array,
  ?array $keysToRemove = null
): array {
  $keysToRemove ??= [
    'chiHaInseritoCliente',
    'chiHaModificatoCliente',
    'eliminatoCliente',
    'idClasseStudente',
    'chiHaInseritoClasse',
    'chiHaModificatoClasse',
    'elminataClasse',
    'idClasseAutoIncrement',
    'idScuolaClasse',
    'idScuolaAutoIncrement',
    'chiHaInseritoScuola',
    'chiHaModificatoScuola',
    'eliminataScuola',
    'idClienteAutoIncrement',
    'idInsegnateAutoINcrement',
    'idAddRegolaAutoIncrement',
    'idMainRegola',
  ];

  foreach ($array as $key => $value) {
    if (in_array($key, $keysToRemove, true)) {
      unset($array[$key]);
      continue;
    }

    if (is_array($value)) {
      $array[$key] = array_remove_keys_recursive($value, $keysToRemove);
    }
  }

  return $array;
}

/**
 * Appiattisce un array multidimensionale.
 *
 * Esempio:
 * ['a' => ['b' => 1]] → ['a.b' => 1]
 *
 * @param array $array
 * @param string $separator
 * @param string $prefix
 * @return array
 */
function array_flatten(
  array $array,
  string $separator = '.',
  string $prefix = ''
): array {
  $result = [];

  foreach ($array as $key => $value) {
    $newKey = $prefix === '' ? $key : $prefix . $separator . $key;

    if (is_array($value)) {
      $result += array_flatten($value, $separator, $newKey);
    } else {
      $result[$newKey] = $value;
    }
  }

  return $result;
}

/**
 * Ritorna true se l'array è associativo.
 *
 * @param array $array
 * @return bool
 */
function array_is_assoc(array $array): bool
{
  if ($array === []) {
    return false;
  }

  return array_keys($array) !== range(0, count($array) - 1);
}

/**
 * Filtra un array mantenendo solo le chiavi consentite.
 *
 * @param array $array
 * @param array $allowedKeys
 * @return array
 */
function array_only(array $array, array $allowedKeys): array
{
  return array_intersect_key(
    $array,
    array_flip($allowedKeys)
  );
}

/**
 * Rimuove valori null, stringhe vuote e array vuoti.
 *
 * @param array $array
 * @return array
 */
function array_clean(array $array): array
{
  return array_filter(
    $array,
    function ($value) {
      if (is_array($value)) {
        return !empty(array_clean($value));
      }
      return $value !== null && $value !== '';
    }
  );
}
