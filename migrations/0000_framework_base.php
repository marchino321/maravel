<?php

/**
 * Migrazione iniziale – Framework base + seed
 * Baseline ufficiale FamilyNest
 *
 * Contiene (schema + record base):
 * - ap_permessi (+ seed)
 * - tbl_utenti (+ seed)
 * - ap_ricordaLogin (+ seed)
 * - db_log (+ seed)
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
    // NB: allineato al dump: DEFAULT uuid() (senza parentesi tonde)
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
        uuidUser VARCHAR(255) NOT NULL DEFAULT uuid(),
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

    // ==========================================================
    // SEED BASE (replica del dump)
    // ==========================================================

    // ap_permessi
    $db->exec("
      INSERT INTO ap_permessi (idPermessoAutoIncrement, descrizionePermesso, permessiEffettivi, eliminatoPermesso)
      VALUES
        (1,  'Admin',          'all', 0),
        (10, 'Collaboratore',  'all', 0),
        (20, 'Altro Permesso', 'all', 0),
        (30, 'Un Altro Permesso','all',0)
      ON DUPLICATE KEY UPDATE
        descrizionePermesso = VALUES(descrizionePermesso),
        permessiEffettivi   = VALUES(permessiEffettivi),
        eliminatoPermesso   = VALUES(eliminatoPermesso);
    ");

    // tbl_utenti (admin)
    // NB: nel dump uuidUser è stringa vuota per l’utente seed. Lo replico identico.
    // NB: JSON DefaultSetting va inserito come stringa.
    $hashAdmin = '$2y$12$F8KFn1TdhASIIWr4.jD14.85Xq9BTjPoPaH4e4dD.sMVpkAMMTnZ2';
    $db->exec("
      INSERT INTO tbl_utenti (
        idUtenteAutoIncrement,
        nomeUtente,
        cognomeUtente,
        passwordUtente,
        emailUtente,
        cellulareRecapitoUtente,
        registrazioneUtente,
        permessiUtente,
        ultimoLogin,
        eliminatoUtente,
        ultimoCambioPassword,
        avatar_utente,
        uuidUser,
        DefaultSetting
      ) VALUES (
        1,
        'Marco',
        'Dattisi',
        '$hashAdmin',
        'admin@admin.it',
        '+39123456789',
        '2021-10-14 17:06:41',
        1,
        '2026-01-10 07:08:11',
        0,
        '2021-10-15 20:29:30',
        '/App/public/img_avatar/26892b67838e@1634378965.png',
        '',
        '{\"mode\":\"light\",\"width\":\"fluid\",\"menuPosition\":\"fixed\",\"sidebar\":{\"color\":[\"dark\"]},\"topbar\":{\"color\":[\"dark\"]},\"showRightSidebarOnPageLoad\":true}'
      )
      ON DUPLICATE KEY UPDATE
        nomeUtente = VALUES(nomeUtente),
        cognomeUtente = VALUES(cognomeUtente),
        passwordUtente = VALUES(passwordUtente),
        emailUtente = VALUES(emailUtente),
        cellulareRecapitoUtente = VALUES(cellulareRecapitoUtente),
        permessiUtente = VALUES(permessiUtente),
        ultimoLogin = VALUES(ultimoLogin),
        eliminatoUtente = VALUES(eliminatoUtente),
        ultimoCambioPassword = VALUES(ultimoCambioPassword),
        avatar_utente = VALUES(avatar_utente),
        uuidUser = VALUES(uuidUser),
        DefaultSetting = VALUES(DefaultSetting);
    ");

    // ap_ricordaLogin (token base)
    $db->exec("
      INSERT INTO ap_ricordaLogin (token_hash, utenteIdAutoIncrement, scadenza_at)
      VALUES (
        '0d2eb3299c9d632c97b62c5ceb95296a0dce87f0d28503ac8d139a6b4fa75766',
        1,
        '2026-02-09 07:08:11'
      )
      ON DUPLICATE KEY UPDATE
        utenteIdAutoIncrement = VALUES(utenteIdAutoIncrement),
        scadenza_at = VALUES(scadenza_at);
    ");

    // db_log (riga base)
    $db->exec("
      INSERT INTO db_log (
        id, table_name, record_id, action, old_data, new_data, user_id, created_at
      ) VALUES (
        1,
        'tbl_utenti',
        1,
        'Aggiornamento',
        '{\"idUtenteAutoIncrement\":1,\"nomeUtente\":\"Marco\",\"cognomeUtente\":\"Dattisi\",\"passwordUtente\":\"$hashAdmin\",\"emailUtente\":\"admin@admin.it\",\"cellulareRecapitoUtente\":\"+39123456789\",\"registrazioneUtente\":\"2021-10-14 19:06:41\",\"permessiUtente\":1,\"ultimoLogin\":\"2026-01-09 20:28:38\",\"eliminatoUtente\":0,\"ultimoCambioPassword\":\"2021-10-15 20:29:30\",\"avatar_utente\":\"\\\\/App\\\\/public\\\\/img_avatar\\\\/26892b67838e@1634378965.png\",\"uuidUser\":\"\",\"DefaultSetting\":\"{\\\\\"mode\\\\\":\\\\\"light\\\\\",\\\\\"width\\\\\":\\\\\"fluid\\\\\",\\\\\"menuPosition\\\\\":\\\\\"fixed\\\\\",\\\\\"sidebar\\\\\":{\\\\\"color\\\\\":[\\\\\"dark\\\\\"]},\\\\\"topbar\\\\\":{\\\\\"color\\\\\":[\\\\\"dark\\\\\"]},\\\\\"showRightSidebarOnPageLoad\\\\\":true}\"}',
        '{\"ultimoLogin\":{\"old\":\"2026-01-09 20:28:38\",\"new\":\"2026-01-10 07:08:11\"}}',
        'Marco Dattisi',
        '2026-01-10 07:08:11'
      )
      ON DUPLICATE KEY UPDATE
        table_name = VALUES(table_name),
        record_id  = VALUES(record_id),
        action     = VALUES(action),
        old_data   = VALUES(old_data),
        new_data   = VALUES(new_data),
        user_id    = VALUES(user_id),
        created_at = VALUES(created_at);
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
