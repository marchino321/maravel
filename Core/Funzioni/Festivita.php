<?php

use App\Config;

if (!defined("CLI_MODE")) {
    defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}
function getItalianHolidays($year, $year1)
{
    // Festività fisse
    $anno = date("Y", strtotime($year));
    $holidays = [
        "$anno-01-01" => "Capodanno",
        "$anno-01-06" => "Epifania",
        "$anno-04-25" => "Festa della Liberazione",
        "$anno-05-01" => "Festa dei Lavoratori",
        "$anno-06-02" => "Festa della Repubblica",
        "$anno-08-15" => "Ferragosto",
        "$anno-11-01" => "Ognissanti",
        "$anno-12-08" => "Immacolata Concezione",
        "$anno-12-25" => "Natale",
        "$anno-12-26" => "Santo Stefano"
    ];

    // Calcolo Pasqua (algoritmo computus)

    $easter = easter_date((int)$anno); // timestamp di Pasqua

    $pasqua = date("Y-m-d", $easter);
    $pasquetta = date("Y-m-d", strtotime("+1 day", $easter));

    $holidays[$pasqua] = "Pasqua";
    $holidays[$pasquetta] = "Lunedì dell’Angelo (Pasquetta)";

    //ksort($holidays); // Ordina per data
    $anno1 = date("Y", strtotime($year1));
    $holidays1 = [
        "$anno1-01-01" => "Capodanno",
        "$anno1-01-06" => "Epifania",
        "$anno1-04-25" => "Festa della Liberazione",
        "$anno1-05-01" => "Festa dei Lavoratori",
        "$anno1-06-02" => "Festa della Repubblica",
        "$anno1-08-15" => "Ferragosto",
        "$anno1-11-01" => "Ognissanti",
        "$anno1-12-08" => "Immacolata Concezione",
        "$anno1-12-25" => "Natale",
        "$anno1-12-26" => "Santo Stefano"
    ];

    // Calcolo Pasqua (algoritmo computus)

    $easter1 = easter_date((int)$anno1); // timestamp di Pasqua
    $pasqua1 = date("Y-m-d", $easter1);
    $pasquetta1 = date("Y-m-d", strtotime("+1 day", $easter1));

    $holidays1[$pasqua1] = "Pasqua";
    $holidays1[$pasquetta1] = "Lunedì dell’Angelo (Pasquetta)";


    $ritorno = array_merge($holidays, $holidays1);
    ksort($ritorno); // Ordina per data



    return $ritorno;
}


function getWeekdaysBetween($startDate, $endDate, $weekdays = [1])
{
    /**
     * $weekdays: array di giorni della settimana (1 = Lunedì ... 7 = Domenica)
     * es: [1,4] → tutti i lunedì e giovedì
     */
    $start = new DateTime($startDate);
    $end   = new DateTime($endDate);

    $days = [];

    // Ciclo giorno per giorno
    while ($start <= $end) {
        if (in_array($start->format('N'), haystack: $weekdays)) {
            $days[] = $start->format("Y-m-d");
        }
        $start->modify("+1 day");
    }

    return $days;
}

function DammiTraduzioneGiorni()
{
    return [
        "Sunday" => "Domenica",
        "Monday" => "Lunedì",
        "Tuesday" => "Martedì",
        "Wednesday" => "Mercoledì",
        "Thursday" => "Giovedì",
        "Friday" => "Venerdì",
        "Saturday" => "Sabato"
    ];
}

function DammiTraduzioneMesi()
{
    return [
        "January" => "Gennaio",
        "February" => "Febbraio",
        "March" => "Marzo",
        "April" => "Aprile",
        "May" => "Maggio",
        "June" => "Giugno",
        "July" => "Luglio",
        "August" => "Agosto",
        "September" => "Settembre",
        "October" => "Ottobre",
        "November" => "Novembre",
        "December" => "Dicembre"
    ];
}

function removeKeysRecursive(array $array): array
{
    $keysToRemove = [
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
        if (in_array($key, $keysToRemove)) {
            unset($array[$key]);
        } elseif (is_array($value)) {
            $array[$key] = removeKeysRecursive($value);
        }
    }

    return $array;
}

function normalizzaValore($valore): ?string
{
    if ($valore === null) return null;
    if (is_array($valore)) return json_encode($valore); // se arriva array, eviti errore

    $v = trim((string)$valore);

    // Percentuali
    if (strpos($v, '%') !== false) {
        $v = str_replace('%', '', $v);
        $v = str_replace('.', '', $v);  // rimuove separatori migliaia
        $v = str_replace(',', '.', $v); // virgola -> punto
        $num = floatval($v);
        return number_format($num, 2, '.', '');
    }

    // Valuta
    if (strpos($v, '€') !== false) {
        $v = str_replace('€', '', $v);
    }

    // Rimuove eventuali spazi extra
    $v = str_replace(' ', '', $v);
    // Virgola italiana → punto decimale
    $v = str_replace(',', '.', $v);

    if (is_numeric($v)) {
        return number_format((float)$v, 2, '.', '');
    }

    return $valore; // se non è numero/valuta/percentuale, restituisci com’è
}


function GetData($data)
{
    if( $data !== "0000-00-00 00:00:00"){
        return date('d-m-Y H:i',  strtotime($data));
    }else{
        return "Nessuna Data";
    }
}