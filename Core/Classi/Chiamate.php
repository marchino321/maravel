<?php

namespace Core\Classi;

use App\Config;
use App\Debug;
use PDO;
use PDOException;
use Core\Model;


if (!defined("CLI_MODE")) {
    defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}
/**
 * Classe Chiamate
 * 
 * Builder per operazioni CRUD su database MySQL.
 * Funzioni principali:
 *  - salva(): inserimento record
 *  - seleziona(): selezione record
 *  - aggiorna(): aggiornamento record
 *  - Cancella(): cancellazione record
 *  - DoppiDinamica(): controllo duplicati
 *  - DropAllTable(): elimina tutte le tabelle
 *  - Pulisci(): pulizia e normalizzazione dati
 *  - PrevieniAttacchiSQL(): protezione input
 */
class Chiamate extends Model
{
    public array $errori = [];
    public bool $show_degug = false;

    private array $formats = [];
    private string $tabella = '';
    private string $logFile;
    private DbLogger $dbLogger;

    public function __construct()
    {
        parent::__construct(null); // usa connessione automatica dal Model

        $json_file = __DIR__ . '/../../ConfigFiles/setting.json';
        if (file_exists($json_file)) {
            $risultato = json_decode(file_get_contents($json_file), true);
            $this->formats = $risultato['date_custom'] ?? [];
        }

        $this->dbLogger = new DbLogger($this->db); // inizializza logger DB
        $this->logFile = $_SERVER['DOCUMENT_ROOT'] . '/logs/chiamate_errors.log';
    }

    // ---------------- SALVA ----------------
    public function salva(string $tabella, array $dati): int
    {
        // Recupera colonne valide della tabella
        $colonne = $this->getColonne($tabella);

        // Filtra i dati
        $datiValidi = array_intersect_key($dati, array_flip($colonne));

        if (empty($datiValidi)) {
            Debug::log("❌ Nessun campo valido per INSERT in $tabella", "DB");
            return 0;
        }

        $campi = array_keys($datiValidi);
        $segnaposto = array_map(fn($c) => ':' . $c, $campi);

        $sql = "INSERT INTO $tabella (" . implode(",", $campi) . ")
            VALUES (" . implode(",", $segnaposto) . ")";
        $stmt = $this->db->prepare($sql);

        foreach ($datiValidi as $campo => $val) {
            $stmt->bindValue(':' . $campo, $val);
        }

        $stmt->execute();
        $lastID = (int)$this->db->lastInsertId();
        $this->dbLogger->logInsert($tabella, $lastID, $dati);
        return $lastID;
    }

    // ---------------- AGGIORNA ----------------
    public function aggiorna(string $tabella, array $dati, string $chiave, $valore): bool
    {
        // Recupera colonne valide della tabella
        $colonne = $this->getColonne($tabella);

        // Filtra i dati
        $datiValidi = array_intersect_key($dati, array_flip($colonne));

        if (empty($datiValidi)) {
            Debug::log("❌ Nessun campo valido per UPDATE in $tabella", "DB");
            return false;
        }

        $set = implode(", ", array_map(fn($c) => "$c = :$c", array_keys($datiValidi)));

        $sql = "UPDATE $tabella SET $set WHERE $chiave = :chiave";
        $stmt = $this->db->prepare($sql);

        foreach ($datiValidi as $campo => $val) {
            $stmt->bindValue(':' . $campo, $val);
        }
        $oldData = $this->seleziona($tabella, $chiave, $valore);
        $stmt->bindValue(':chiave', $valore);
        $this->dbLogger->logUpdate($tabella, $valore, $oldData[0], $dati);
        return $stmt->execute();
    }

    // ---------------- CANCELLA ----------------
    public function Cancella(string $tabella, string $campo, string $input): int
    {
        $this->tabella = $tabella;

        $oldRows = $this->seleziona($tabella, $campo, $input);
        $oldData = $oldRows[0] ?? [];

        $sql = "DELETE FROM `$tabella` WHERE $campo = :input";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':input', $input, PDO::PARAM_STR);

        try {
            $stmt->execute();
            $this->errori = $stmt->errorInfo();

            // Log automatico cancellazione
            $this->dbLogger->logDelete($this->tabella, (int)$input, $oldData);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->logError($sql, $e);
            return 0;
        }
    }

    // ---------------- SELEZIONA ----------------
    public function seleziona(string $tabella, string $dove, mixed $idRicerca = 0, mixed $and = '', mixed $join = ''): array|false
    {
        $this->tabella = $tabella;
        $collegamento = $this->buildJoin($join);

        $sql = "SELECT * FROM `{$this->tabella}` $collegamento";
        $valori = [];

        // Se non è la chiamata "dummy" (1,1) aggiungiamo la WHERE
        if (!($dove === "1" && $idRicerca === "1")) {
            $sql .= " WHERE $dove = :$dove";
            $valori[$dove] = $idRicerca;
        }

        if (is_array($and)) {
            foreach ($and as $key => $val) {
                $sql .= " AND $key = :$key";
                $valori[$key] = $val;
            }
        } elseif (!empty($and)) {
            $sql .= " $and";
        }

        $stmt = $this->db->prepare($sql);
        $this->bindParams($stmt, $valori);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);

        if ($this->show_degug) echo $sql;

        try {
            $stmt->execute();
            $this->errori = $stmt->errorInfo();
            return $stmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            $this->logError($sql, $e);
            return [];
        }
    }


    // ---------------- DOPPI DINAMICA ----------------
    public function DoppiDinamica(string $tabella, string $campo, string $input): int
    {
        $sql = "SELECT COUNT(*) as cnt FROM `$tabella` WHERE LOWER($campo) = LOWER(:input)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':input', $input, PDO::PARAM_STR);

        try {
            $stmt->execute();
            $this->errori = $stmt->errorInfo();
            return (int)$stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
        } catch (PDOException $e) {
            $this->logError($sql, $e);
            return 0;
        }
    }



    // ---------------- DROP ALL TABLE ----------------
    public function DropAllTable(): void
    {
        $this->db->exec("SET foreign_key_checks = 0");

        $tables = $this->db->query("SHOW TABLES")->fetchAll(PDO::FETCH_NUM);
        foreach ($tables as $table) {
            $this->db->exec("DROP TABLE IF EXISTS `{$table[0]}`");
        }

        $this->db->exec("SET foreign_key_checks = 1");
    }

    // ---------------- PULISCI ----------------
    public function Pulisci(?string $val): ?string
    {
        if ($val === null || trim($val) === '') return null;
        $this->PrevieniAttacchiSQL($val);
        $val = str_replace('`', '\'', $val);

        // JSON valido
        if ($this->json_validate($val)) return $val;

        // Normalizza soldi e percentuali
        if (preg_match('/^\s*€?\s?[\d\.]+,\d{2}\s*$/', trim($val))) {
            $normalized = str_replace(['€', '.', ' '], '', $val);
            $normalized = str_replace(',', '.', $normalized);
            return number_format((float)$normalized, 2, '.', '');
        }

        if (preg_match('/^\s*%?\s?\d+(?:[.,]\d+)?\s*%?\s*$/', trim($val))) {
            $normalized = str_replace(['%', ' '], '', $val);
            $normalized = str_replace(',', '.', $normalized);
            return number_format((float)$normalized, 2, '.', '');
        }

        // Date
        if ($this->checkIsAValidDate($val)) {
            foreach ($this->formats as $format) {
                $date = \DateTime::createFromFormat($format, $val);
                if ($date !== false) return $date->format('Y-m-d H:i:s');
            }
            return date('Y-m-d H:i:s', strtotime($val));
        }

        // Password hash
        $passwordInfo = password_get_info($val);
        if ($passwordInfo['algo'] !== 0) return $val;

        return $val;
    }

    public function checkIsAValidDate(string $myDateString): bool
    {
        return (bool)strtotime($myDateString);
    }

    public function GetError(): array
    {
        return empty($this->errori) ? [200 => 'No Error'] : $this->errori;
    }

    private function logError(string $sql, PDOException $e): void
    {
        $message = "[" . date('Y-m-d H:i:s') . "] SQL Error: {$e->getMessage()} | Query: {$sql}\n";
        file_put_contents($this->logFile, $message, FILE_APPEND);
        $this->errori = [$e->getCode() => $e->getMessage()];
    }

    private function bindParams(\PDOStatement $stmt, array $valori): void
    {
        foreach ($valori as $key => $val) {
            $type = match (true) {
                is_int($val) => PDO::PARAM_INT,
                is_null($val) => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };
            $stmt->bindValue(":$key", $val, $type);
        }
    }

    public function PrevieniAttacchiSQL(?string $stringa): void
    {
        if ($stringa === null || $stringa === '') return;

        $patternSospetti = [
            'union select',
            'drop table',
            'insert into',
            'update set',
            'delete from',
            'alter table',
            'exec ',
            'sleep(',
            'or 1=1',
            '--',
            ';--',
            '/*',
            '*/',
            '@@',
        ];

        foreach ($patternSospetti as $pattern) {
            if (stripos($stringa, $pattern) !== false) {
                $log = "[" . date('Y-m-d H:i:s') . "] Input sospetto: -" . $stringa . "- | IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'CLI') . "\n";
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/logs/input_sospetti.log', $log, FILE_APPEND);
                http_response_code(400);
                die(json_encode(['error' => 'Input sospetto rilevato, contattare l\'amministratore']));
            }
        }
    }

    private function buildJoin(mixed $join): string
    {
        if (!is_array($join)) return '';
        $result = '';
        foreach ($join as $table => $details) {
            $on = '';
            foreach ($details as $k => $v) {
                if ($k !== 'JOIN') $on .= " ON $k = $v";
            }
            $result .= " " . $details['JOIN'] . ' JOIN ' . $table . $on;
        }
        return $result;
    }
    

    private function json_validate(string $json): bool
    {
        json_decode($json);
        return (json_last_error() === JSON_ERROR_NONE);
    }
    /**
     * Recupera le colonne di una tabella
     */
    private function getColonne(string $tabella): array
    {
        $stmt = $this->db->query("DESCRIBE $tabella");
        $colonne = [];
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $colonne[] = $r['Field'];
        }
        return $colonne;
    }
}
