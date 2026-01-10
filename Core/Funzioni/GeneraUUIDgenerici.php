<?php

use App\Config;

if (!defined("CLI_MODE")) {
    defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
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