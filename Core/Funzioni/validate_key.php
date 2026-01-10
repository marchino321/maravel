<?php

use App\Config;

if (!defined("CLI_MODE")) {
    defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}
function isValidApiKey($key): bool
{
    // Qui puoi verificare la chiave nel database o file
    $validKeys = ['mia-chiave-super-segreta', 'mia-chiave-super-segreta', 'altra-chiave-segreta']; // esempio
    return in_array($key, $validKeys, true);
}