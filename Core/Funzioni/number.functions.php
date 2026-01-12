<?php

declare(strict_types=1);

use App\Config;

if (!defined('CLI_MODE')) {
  defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}
/**
 * =========================================
 * NUMBER FUNCTIONS – CORE
 * =========================================
 * Utilità numeriche pure
 * - Nessun HTML
 * - Nessuna sessione
 * - Nessun Theme
 * =========================================
 */



/**
 * Normalizza un valore numerico (valuta, percentuale, stringa).
 *
 * Esempi:
 *  "1.234,50 €" → "1234.50"
 *  "10%"        → "10.00"
 *  15           → "15.00"
 *
 * @param mixed $value
 * @param int $decimals
 * @return string|null
 */
function number_normalize(mixed $value, int $decimals = 2): ?string
{
  if ($value === null) {
    return null;
  }

  if (is_array($value)) {
    return json_encode($value);
  }

  $v = trim((string)$value);

  // percentuale
  if (str_contains($v, '%')) {
    $v = str_replace('%', '', $v);
  }

  // valuta
  $v = str_replace(['€', '$', '£'], '', $v);

  // rimuove spazi
  $v = str_replace(' ', '', $v);

  // separatori italiani → formato float
  $v = str_replace('.', '', $v);
  $v = str_replace(',', '.', $v);

  if (!is_numeric($v)) {
    return null;
  }

  return number_format((float)$v, $decimals, '.', '');
}

/**
 * Verifica se un valore è numerico valido (accetta formati umani).
 *
 * @param mixed $value
 * @return bool
 */
function number_is_valid(mixed $value): bool
{
  return number_normalize($value) !== null;
}

/**
 * Calcola percentuale.
 *
 * @param float|int|string $part
 * @param float|int|string $total
 * @param int $decimals
 * @return float
 */
function number_percent(
  float|int|string $part,
  float|int|string $total,
  int $decimals = 2
): float {
  $part  = (float) number_normalize($part);
  $total = (float) number_normalize($total);

  if ($total == 0.0) {
    return 0.0;
  }

  return round(($part / $total) * 100, $decimals);
}

/**
 * Arrotonda sempre verso l'alto.
 */
function number_round_up(float $value, int $precision = 2): float
{
  $factor = 10 ** $precision;
  return ceil($value * $factor) / $factor;
}

/**
 * Arrotonda sempre verso il basso.
 */
function number_round_down(float $value, int $precision = 2): float
{
  $factor = 10 ** $precision;
  return floor($value * $factor) / $factor;
}

/**
 * Format numerico per output umano (IT).
 *
 * @param float|int|string $value
 * @param int $decimals
 * @return string
 */
function number_format_it(
  float|int|string $value,
  int $decimals = 2
): string {
  $num = number_normalize($value, $decimals);

  if ($num === null) {
    return '0,00';
  }

  return number_format((float)$num, $decimals, ',', '.');
}

/**
 * Limita un numero entro un range.
 */
function number_clamp(
  float|int $value,
  float|int $min,
  float|int $max
): float|int {
  return max($min, min($value, $max));
}
