<?php

namespace Core\Classi;

use PDO;
use App\Config;

if (!defined("CLI_MODE")) {
    defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}
class DbLogger
{
    private PDO $pdo;
    private ?string $userId;

    public function __construct(PDO $pdo, ?string $userId = null)
    {
        $this->pdo = $pdo;
        $this->userId = $userId ?? ($_SESSION[ Config::$SESSION_USER_KEY ] ?? null);
    }

    /**
     * Log generico
     */
    public function log(string $table, string $action, ?int $recordId = null, array $oldData = [], array $newData = []): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO db_log (table_name, record_id, action, old_data, new_data, user_id, created_at)
            VALUES (:table_name, :record_id, :action, :old_data, :new_data, :user_id, NOW())
        ");
        if($table !== "ap_ricordaLogin" ){
            $stmt->execute([
                ':table_name' => $table,
                ':record_id' => $recordId,
                ':action' => $action,
                ':old_data' => json_encode($oldData),
                ':new_data' => json_encode($newData),
                ':user_id' => $_SESSION['nomeUtente'] . " " . $_SESSION['cognomeUtente']
            ]);
        }
        
    }

    /**
     * Log INSERT
     */
    public function logInsert(string $table, int $recordId, array $newData): void
    {
        $this->log($table, 'Inserimento', $recordId, [], $newData);
    }

    /**
     * Log UPDATE
     */
    public function logUpdate(string $table, int $recordId, array $oldData, array $newData): void
    {
        // Mantieni solo i campi modificati
        $changes = [];
        foreach ($newData as $key => $newVal) {
            $oldVal = $oldData[$key] ?? null;
            if ($newVal !== $oldVal) {
                $changes[$key] = [
                    'old' => $oldVal,
                    'new' => $newVal
                ];
            }
        }

        // Se non ci sono cambiamenti, non loggare
        if (empty($changes)) return;

        $this->log($table, 'Aggiornamento', $recordId, $oldData, $changes);
    }

    /**
     * Log DELETE
     */
    public function logDelete(string $table, int $recordId, array $oldData): void
    {
        $this->log($table, 'Cancellazione', $recordId, $oldData, []);
    }
}
