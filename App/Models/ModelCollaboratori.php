<?php

namespace App\Models;

use App\Config;
use PDO;
use Core\Classi\Token;
use Core\Classi\Chiamate;
use Core\Classi\Debug;
use Core\Classi\Flash;
use Core\Components\DynamicTable;
use Core\Helpers\UploadHelper;

if (!defined("CLI_MODE")) {
  defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}

class ModelCollaboratori extends \Core\Model
{
  public static function GetAllCollaboratori()
  {
    $c = new Chiamate();

    $utenti = $c->seleziona("tbl_utenti", "1", "1");
    $permessi = $c->seleziona("ap_permessi", "1", "1");
    $columns = [
      'ID' => fn($row) => ($row['idUtenteAutoIncrement'] == 1) ? "0" : 'CG' . str_pad((string)$row['idUtenteAutoIncrement'], 5, '0', STR_PAD_LEFT),
      'Nome' => fn($row) => $row['nomeUtente'] . " " . $row['cognomeUtente'],
      'Email' => fn($row) => '<a href="mailto:' . $row['emailUtente'] . '">' . $row['emailUtente'] . '</a>',
      'Ultimo Login' => fn($row) => GetData($row['ultimoLogin']),
      'Ultimo Cambio PW' => fn($row) => GetData($row['ultimoCambioPassword']),
      'Permessi' => function ($row, $preloaded) {
        foreach ($preloaded as $p) {
          if ($p['idPermessoAutoIncrement'] == $row['permessiUtente']) {
            return $p['descrizionePermesso'];
          }
        }
        return "Nessun Permesso";
      },
      'Modifica' => fn($row) => '<a class="btn btn-info" href="/private/collaboratori/aggiungi-modifica-collaboratore/' . $row['idUtenteAutoIncrement'] . '"><i class="fas fa-edit"></i></a>',
    ];
    $options = [
      'tfootClasses' => [
        'Modifica' => 'noInput'
      ],
      'hideZeroRows' => ['ID']
    ];
    $table = new DynamicTable($utenti, $columns, $permessi, $options);
    return $table->render();
  }
  public static function InserisciAggiornaCollaboratore($id, $dati)
  {
    $c = new Chiamate();
    //Importo l'immagine del. profilo se esiste
    Self::EliminaFile($id);
    $uploader = new UploadHelper("/uploads/avatar");
    $results = $uploader->upload("fotoProfilo");
    foreach ($results as $file) {
      if ($file['success']) {
        $dati['avatar_utente'] = $file['path'];
      }
    }
    if ($id == 0) {
      $dati['uuidUser'] = generateUUIDv4();
      $password = $dati['passwordUtente'];
      unset($dati['passwordUtente']);
      $dati['passwordUtente'] = password_hash($password, PASSWORD_DEFAULT);

      $id = $c->salva("tbl_utenti", $dati);
      Flash::AddByKey("insert.ok");

      // salva
    } else {
      //aggiorna
      if ($dati['passwordUtente'] == "") {
        unset($dati['passwordUtente']);
      } else {
        $psw = $dati['passwordUtente'];
        unset($dati['passwordUtente']);
        $dati['passwordUtente'] = password_hash($psw, PASSWORD_DEFAULT);
        $dati['ultimoCambioPassword'] = date('Y-m-d H:i:s');
      }

      $c->aggiorna("tbl_utenti", $dati, "idUtenteAutoIncrement", $id);
      Flash::AddByKey("update.ok");
    }
    return true;
  }
  private static function EliminaFile($id)
  {
    if ($id == 0) return;
    $c = new Chiamate();
    $user = $c->seleziona("tbl_utenti", "idUtenteAutoIncrement", $id);
    if (!empty($user)) {
      if (file_exists($_SERVER['DOCUMENT_ROOT'] . $user[0]['avatar_utente'])) {
        unlink($_SERVER['DOCUMENT_ROOT'] . $user[0]['avatar_utente']);
        return true;
      }
    }
    return false;
  }
  public static function GetCollaboratoreByID($id)
  {
    $c = new Chiamate();
    $Collaboratore = $c->seleziona("tbl_utenti", "idUtenteAutoIncrement", $id);
    return $Collaboratore[0] ?? null;
  }
}
