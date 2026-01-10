<?php

/**
 * Migrazione iniziale – Framework base
 * Baseline ufficiale FamilyNest
 *
 * Contiene:
 * - tbl_utenti
 * - ap_permessi
 * - ap_ricordaLogin
 * - db_log
 *
 * DO NOT MODIFY AFTER FIRST RUN
 */

return [

  'up' => function (PDO $db) {

    // =========================
    // ap_permessi
    // =========================
    $db->exec("
            CREATE TABLE IF NOT EXISTS ap_permessi (
                idPermessoAutoIncrement INT(11) NOT NULL AUTO_INCREMENT,
                descrizionePermesso VARCHAR(255) NOT NULL,
                permessiEffettivi LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
                eliminatoPermesso INT(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (idPermessoAutoIncrement),
                KEY eliminatoPermesso (eliminatoPermesso)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_cs;
        ");

    // =========================
    // tbl_utenti
    // =========================
    $db->exec("
            CREATE TABLE IF NOT EXISTS tbl_utenti (
                idUtenteAutoIncrement INT(11) NOT NULL AUTO_INCREMENT,
                nomeUtente VARCHAR(255) NOT NULL,
                cognomeUtente VARCHAR(255) NOT NULL,
                passwordUtente VARCHAR(255) NOT NULL,
                emailUtente VARCHAR(255) NOT NULL,
                cellulareRecapitoUtente VARCHAR(255) DEFAULT NULL,
                registrazioneUtente TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                permessiUtente INT(11) NOT NULL DEFAULT 1 COMMENT 'riferimento ap_permessi',
                ultimoLogin DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                eliminatoUtente INT(11) NOT NULL DEFAULT 0 COMMENT '0 in corso, 1 eliminato',
                ultimoCambioPassword DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                avatar_utente VARCHAR(255) NOT NULL DEFAULT '/App/public/assets/images/users/default.svg',
                uuidUser VARCHAR(255) NOT NULL DEFAULT (UUID()),
                DefaultSetting LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
                PRIMARY KEY (idUtenteAutoIncrement),
                KEY permessiUtente (permessiUtente),
                KEY emailUtente (emailUtente),
                KEY uuidUser (uuidUser),
                CONSTRAINT tbl_utenti_ibfk_1
                    FOREIGN KEY (permessiUtente)
                    REFERENCES ap_permessi (idPermessoAutoIncrement)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_cs;
        ");

    // =========================
    // ap_ricordaLogin
    // =========================
    $db->exec("
            CREATE TABLE IF NOT EXISTS ap_ricordaLogin (
                token_hash VARCHAR(64) NOT NULL,
                utenteIdAutoIncrement INT(11) NOT NULL,
                scadenza_at DATETIME NOT NULL,
                PRIMARY KEY (token_hash),
                KEY utenteIdAutoIncrement (utenteIdAutoIncrement),
                CONSTRAINT ap_ricordaLogin_ibfk_1
                    FOREIGN KEY (utenteIdAutoIncrement)
                    REFERENCES tbl_utenti (idUtenteAutoIncrement)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_cs;
        ");

    // =========================
    // db_log
    // =========================
    $db->exec("
            CREATE TABLE IF NOT EXISTS db_log (
                id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                table_name VARCHAR(255) NOT NULL COMMENT 'Nome della tabella modificata',
                record_id INT(10) UNSIGNED DEFAULT NULL COMMENT 'ID del record interessato',
                action VARCHAR(50) NOT NULL COMMENT 'Azione eseguita',
                old_data TEXT DEFAULT NULL COMMENT 'Dati precedenti',
                new_data TEXT DEFAULT NULL COMMENT 'Nuovi dati',
                user_id VARCHAR(255) DEFAULT 'system',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
  },

  'down' => function (PDO $db) {

    // ATTENZIONE: ordine inverso per FK
    $db->exec("DROP TABLE IF EXISTS db_log");
    $db->exec("DROP TABLE IF EXISTS ap_ricordaLogin");
    $db->exec("DROP TABLE IF EXISTS tbl_utenti");
    $db->exec("DROP TABLE IF EXISTS ap_permessi");
  }

];
