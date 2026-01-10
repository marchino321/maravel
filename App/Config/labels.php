<?php

use App\Config;

if (!defined("CLI_MODE")) {
    defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}

return [

    // ======================
    // Tabs
    // ======================
    'tab_anagrafica'   => 'Anagrafica',
    'tab_preferenze'   => 'Preferenze',
    'tab_emergenza'    => 'Emergenza',

    // ======================
    // Campi Anagrafica
    // ======================
    'form_nome'        => 'Nome',
    'placeholder_nome' => 'Inserisci il Nome',

    'form_cognome'        => 'Cognome',
    'placeholder_cognome' => 'Inserisci il Cognome',

    'form_data_nascita'        => 'Data di Nascita',
    'placeholder_data_nascita' => 'Inserisci la Data di Nascita',

    'form_email'        => 'Email',
    'placeholder_email' => "Inserisci l'Email",

    'form_telefono'        => 'Telefono',
    'placeholder_telefono' => 'Inserisci il Telefono',

    'form_telefono_extra'        => 'Telefono Extra',
    'placeholder_telefono_extra' => 'Inserisci il Telefono Extra',

    'form_flag_studente'   => 'È Studente',
    'option_studente'      => 'Anche Studente',
    'option_cliente'       => 'Cliente',

    'form_preferenza_contatto'   => 'Preferenza di Contatto',
    'option_email_contatto'      => '📤 Email',
    'option_whatsapp_contatto'   => '📱 WhatsApp',

    'form_lingua'   => 'Lingua',
    'option_italiano' => '🇮🇹 Italiano',
    'option_spagnolo' => '🇪🇸 Spagnolo',
    'option_francese' => '🇫🇷 Francese',
    'option_inglese'  => '🇬🇧 Inglese',

    'form_status'   => 'Status',
    'option_attivo'   => '✅ Attivo',
    'option_inattivo' => '❌ Inattivo',
    'option_sospeso'  => '⏸️ Sospeso',

    'form_indirizzo'        => 'Indirizzo',
    'placeholder_indirizzo' => "Inserisci l'Indirizzo",

    'form_citta'        => 'Città',
    'placeholder_citta' => 'Inserisci la Città',

    'form_provincia'        => 'Provincia',
    'placeholder_provincia' => 'Inserisci la Provincia',

    'form_cap'        => 'CAP',
    'placeholder_cap' => 'Inserisci il CAP',

    // ======================
    // Campi Emergenza
    // ======================
    'form_emergency_contact'        => 'Contatto di emergenza',
    'placeholder_emergency_contact' => 'Inserisci il contatto di emergenza',

    'form_telefono_emergenza'        => 'Telefono',
    'placeholder_telefono_emergenza' => 'Inserisci il telefono',

    'form_telefono_extra_emergenza'        => 'Telefono Extra',
    'placeholder_telefono_extra_emergenza' => 'Inserisci il telefono extra',

    'form_relazione'        => 'Relazione',
    'placeholder_relazione' => 'Inserisci la relazione',

    // ======================
    // Campi Preferenze
    // ======================
    'form_preferenze_primarie'        => 'Preferenze Primarie',
    'placeholder_preferenze_primarie' => 'Scegli un Insegnante Primario',

    'form_preferenze_secondarie'        => 'Preferenze Secondarie',
    'placeholder_preferenze_secondarie' => 'Scegli un Insegnante Secondario',

    'option_seleziona' => '[ Seleziona ]',

    'form_scuole' => 'Scuole',

    // ======================
    // Varie
    // ======================
    'nota_privata' => 'Nota Privata',
    'campi_obbligatori' => 'Tutti i campi con * sono obbligatori',

    // ======================
    // Pulsanti
    // ======================
    'btn_salva' => 'Salva',

    // ======================
    // Menu (già mappati)
    // ======================
    'dashboard'              => 'Dashboard',

    'collaboratori'          => 'Collaboratori',
    'collaboratori_elenco'   => 'Elenco',
    'collaboratori_aggiungi' => 'Aggiungi',

    'sale'          => 'Room',
    'sale_elenco'   => 'Elenco',
    'sale_aggiungi' => 'Aggiungi',

    'servizi'          => 'Servizi',
    'servizi_elenco'   => 'Elenco',
    'servizi_aggiungi' => 'Aggiungi',

    'strumenti'          => 'Strumenti',
    'strumenti_elenco'   => 'Elenco',
    'strumenti_aggiungi' => 'Aggiungi',

    'regole'          => 'Regole',
    'regole_elenco'   => 'Elenco',
    'regole_aggiungi' => 'Aggiungi',

    'insegnanti'          => 'Insegnanti',
    'insegnanti_elenco'   => 'Elenco',
    'insegnanti_aggiungi' => 'Aggiungi',

    'listini'          => 'Listini',
    'listini_elenco'   => 'Elenco',
    'listini_aggiungi' => 'Aggiungi',

    'entita'          => 'Entità',
    'entita_elenco'   => 'Elenco',
    'entita_aggiungi' => 'Aggiungi',

    'clienti'          => 'Clienti',
    'clienti_elenco'   => 'Elenco',
    'clienti_aggiungi' => 'Aggiungi',

    // ======================
    // Plugin conosciuti
    // ======================
    'plugin_test'     => 'Plugin Test',
    'plugin_test_sub' => 'Sotto Voce',

    'welcome'   => 'Benvenuto!',
    'profilo'   => 'Profilo',
    'logout'    => 'Logout',
    'menu_principale' => 'Menu Principale',
    'Ragione Sociale' => 'Ragione Sociale',
    'Inserisci la Ragione Sociale' => 'Inserisci la Ragione Sociale',
    'Partita IVA / Codice Fiscale' => 'Partita IVA / Codice Fiscale',
    'Inserisci la tua Partita IVA o Codice Fiscale' => 'Inserisci la tua Partita IVA o Codice Fiscale',
    'SDI' => 'SDI',
    'Inserisci lo SDI (Sistema di Interscambio)' => 'Inserisci lo SDI (Sistema di Interscambio)',
    'Percentuale Entità' => 'Percentuale Entità',
    'Inserisci la Percentuale dell\'Entità' => 'Inserisci la Percentuale dell\'Entità',
    'Percentuale Studio' => 'Percentuale Studio',
    'Inserisci la Percentuale dello Studio' => 'Inserisci la Percentuale dello Studio',
    'Tipo Conteggio' => 'Tipo Conteggio',
    'In Percentuale' => 'In Percentuale',
    'In Euro' => 'In Euro',
    'Finanziaria' => 'Finanziaria',
    'parte_entita' => 'Parte Entità',
    'parte_studio' => 'Parte Studio',
    'azioni'       => 'Azioni',
    'duplica'      => 'Duplica',
    'modifica'     => 'Modifica',
    // Tabs insegnanti
    'programmazione_disponibilita' => 'Programmazione Disponibilità',
    'modifica_disponibilita'       => 'Modifica Disponibilità',

    // Insegnante
    'nome_insegnante'     => 'Nome Insegnante',
    'cognome_insegnante'  => 'Cognome Insegnante',
    'email_insegnante'    => 'Email Insegnante',
    'telefono_insegnante' => 'Telefono Insegnante',

    // Ruoli
    'istruttore'          => 'Istruttore',
    'istruttore_senior'   => 'Istruttore Senior',
    'capo_dipartimento'   => 'Capo Dipartimento',

    // Status insegnante
    'tournee'             => 'Tournée',

    // Extra
    'dal'                 => 'Dal',
    'al'                  => 'Al',
    'biografia'           => 'Biografia',
    'nota'                => 'Nota',
    'giorno'              => 'Giorno',
    'dalle'               => 'Dalle',
    'alle'                => 'Alle',
    'giorno_disponibile'  => 'Giorno Disponibile',
    'dalle_ore'           => 'Dalle Ore',
    'alle_ore'            => 'Alle Ore',
];
