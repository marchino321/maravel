<?php

namespace Core;

use PDO;

class Permission extends Model
{
    public static function can(int $userId, string $azione = 'read', ?string $risorsa = null): bool
    {
        $db = self::getDb();

        // Se non passo la risorsa → costruiscila automaticamente
        if ($risorsa === null) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = $backtrace[1] ?? [];
            $class  = $caller['class'] ?? '';
            $func   = $caller['function'] ?? '';
            $risorsa = str_replace("App\\Controllers\\", "", $class) . "@" . $func;
        }

        $sql = "SELECT pr.canRead, pr.canCreate, pr.canEdit, pr.canDelete, p.permessiEffettivi
                FROM tbl_utenti u
                JOIN ap_permessi p ON u.permessiUtente = p.idPermessoAutoIncrement
                LEFT JOIN ap_risorse r ON r.nomeRisorsa = ?
                LEFT JOIN ap_permessi_risorse pr ON pr.idPermesso = p.idPermessoAutoIncrement 
                                                AND pr.idRisorsa = r.idRisorsa
                WHERE u.idUtenteAutoIncrement = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$risorsa, $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return false;

        // Se ruolo ha permesso "all" → bypass
        if ($row['permessiEffettivi'] === 'all') return true;

        $map = [
            'read'   => 'canRead',
            'create' => 'canCreate',
            'edit'   => 'canEdit',
            'delete' => 'canDelete',
        ];

        return isset($map[$azione]) && $row[$map[$azione]] == 1;
    }
}