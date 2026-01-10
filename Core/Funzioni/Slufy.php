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