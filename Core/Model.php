<?php

declare(strict_types=1);

namespace Core;

use Core\Classi\Flash;
use PDO;
use PDOException;
use App\Config;
use App\Debug;

if (!defined("CLI_MODE")) {
    defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}

/**
 * Classe base per tutti i modelli.
 *
 * - Gestisce la connessione al database tramite PDO (singleton).
 * - Tutti i modelli devono estendere questa classe.
 */
class Model
{
    /** @var PDO|null Connessione PDO condivisa */
    protected static ?PDO $sharedDb = null;

    /** @var PDO Connessione PDO attiva per l’istanza */
    protected PDO $db;

    /**
     * Costruttore del modello.
     * Usa la connessione condivisa, se non esiste la crea.
     *
     * @param PDO|null $pdo Connessione PDO già configurata (opzionale)
     */
    public function __construct(?PDO $pdo = null)
    {
        if ($pdo !== null) {
            // Se gli passo una connessione dall'esterno
            $this->db = $pdo;
            Debug::log("🔌 Connessione PDO passata direttamente al modello.", 'MODEL');
            return;
        }

        // Se non esiste già, creala
        if (self::$sharedDb === null) {
            if (empty(Config::$dbConfig['host'])) {
                Debug::log("❌ Config DB mancante. Devi lanciare l'installer.", 'MYSQL-DB');
                throw new \RuntimeException("❌ Config DB mancante. Devi lanciare l'installer.");
            }

            $host    = Config::$dbConfig['host'] ?? 'localhost';
            $port    = Config::$dbConfig['port'] ?? 3306;
            $dbname  = Config::$dbConfig['dbname'] ?? '';
            $user    = Config::$dbConfig['user'] ?? 'root';
            $pass    = Config::$dbConfig['pass'] ?? '';
            $charset = Config::$dbConfig['charset'] ?? 'utf8mb4';

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
            Debug::log("🛠 Preparazione connessione DB: {$dsn}", 'MYSQL-DB');

            try {
                self::$sharedDb = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);

                Debug::log("✅ Connessione DB riuscita → {$dbname}@{$host}:{$port}", 'MYSQL-DB');
            } catch (PDOException $e) {
                Flash::AddByKey('db.crash');
                Debug::log("❌ Errore connessione DB: " . $e->getMessage(), 'MYSQL-DB');
                throw $e;
            }
        }

        // Usa la connessione condivisa
        $this->db = self::$sharedDb;
    }

    /**
     * Restituisce la connessione PDO attiva
     */
    public function getConnection(): PDO
    {
        return $this->db;
    }
    public static function getDb(): PDO
    {
        if (self::$sharedDb === null) {
            new static(); // forza init costruttore
        }
        return self::$sharedDb;
    }
}
