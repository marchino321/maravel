<?php

namespace App\Controllers\Private;

use App\Debug;
use Core\Classi\Flash;
use Core\Controller;
use Core\View\TwigManager;
use Core\View\MenuManager;
use App\Config;
use Core\Components\DropZone;
use Core\Components\Quill;
use Core\View\ThemeManager;

if (!defined("CLI_MODE")) {
  defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}
/**
 * Controller Profilo
 *
 * Gestione visualizzazione e aggiornamento profilo utente
 *
 * PHP 8.3
 * By Marco Dattisi
 */
class Profilo extends Controller
{
  /**
   * Istanza Chiamate per DB
   */
  private string $tabella;


  /**
   * Costruttore
   */
  public function __construct(TwigManager $twigManager, MenuManager $menuManager)
  {
    parent::__construct($twigManager);
    $this->tabella = "tbl_utenti";
    $this->menuManager = $menuManager;

    Debug::log("Controller Profilo inizializzato", "PROFILO");
  }

  /**
   * Mostra la pagina per modificare il profilo dell'utente
   */
  public function ModificaProfilo(): void
  {

    $dropzone = new DropZone([
      'name'    => 'fotoProfilo',
      'message' => 'Trascina qui la nuova foto profilo'
    ]);
    $ritorno = [
      'ProfiloUpload' => $dropzone->render(),
      //'Quill' => Quill::make('contenuto', 'ciao'),
    ];
    Debug::log("Rendering pagina profilo utente", "PROFILO");

    echo $this->twigManager->getTwig()->render('Private/Profilo/profilo.html', $ritorno);
  }

  /**
   * Salva o aggiorna il profilo dell'utente
   */
  public function SalvaAggiornaProfilo(): void
  {
    Debug::log("Salvataggio/aggiornamento profilo utente", "PROFILO");

    // Decodifica le impostazioni di default dalla sessione
    $arraySetting = json_decode($_SESSION['DefaultSetting'] ?? '{}', true);

    // Aggiorna colori sidebar e topbar dai dati POST
    $setting['sidebar']['color'][0] = $_POST['leftsidebar-color'] ?? '';
    $setting['topbar']['color'][0] = $_POST['topbar-color'] ?? '';

    // Merge tra impostazioni esistenti e quelle nuove
    $result = array_merge($arraySetting, $setting);

    // Rimuove i campi non necessari da POST
    unset($_POST['leftsidebar-color'], $_POST['topbar-color']);

    // Salva le impostazioni aggiornate in POST per l'inserimento nel DB
    $_POST['DefaultSetting'] = json_encode($result);

    // Gestione cambio password
    if (!empty($_POST['passwordUtente'])) {
      $password = password_hash($_POST['passwordUtente'], PASSWORD_DEFAULT);
      $_POST['passwordUtente'] = $password;
      $_POST['ultimoCambioPassword'] = date('Y-m-d H:i:s');
    } else {
      unset($_POST['passwordUtente']); // Non aggiornare se vuota
    }

    // Gestione upload avatar
    // if (!empty($_FILES['foto_profilo']['name'])) {
    //     $file = new \MarcoUpload\MarcoUpload($_SERVER['DOCUMENT_ROOT']);
    //     $percorso = $file->upload($_FILES['foto_profilo'], [
    //         'move' => '/App/public/assets/img_avatar',
    //         'size' => 2_000_000,
    //         'type' => ['jpg', 'png', 'jpeg', 'gif']
    //     ]);

    //     // Salva percorso relativo nel DB
    //     $_POST['avatar_utente'] = str_replace([$_SERVER['DOCUMENT_ROOT']], [''], $percorso);
    // }

    // Salva i dati nel database
    $rowEffect = $this->c->aggiorna('tbl_utenti', $_POST, 'idUtenteAutoIncrement', $_SESSION['idUtenteAutoIncrement'] ?? 0);
    Debug::log("Dati profilo aggiornati: " . json_encode($_POST), "PROFILO");

    if ($rowEffect <= 0) {
      Debug::log("Profilo non aggiornato: " . json_encode($this->c->GetError()), "PROFILO");
    }

    // Mostra messaggio di successo
    //  Flash::AddMex('Profilo aggiornato correttamente', Flash::SUCCESS, 'Aggiornamento profilo');

    // Reindirizza alla pagina precedente
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'), true, 303);
    exit;
  }

  /**
   * Funzione ricorsiva per fare merge tra due array preservando valori distinti
   *
   * @param array $array1 Array principale
   * @param array $array2 Array da unire
   * @return array Array risultante unito ricorsivamente
   */
  public function array_merge_recursive_distinct(array &$array1, array &$array2): array
  {
    $merged = $array1;

    foreach ($array2 as $key => &$value) {
      if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
        $merged[$key] = $this->array_merge_recursive_distinct($merged[$key], $value);
      } else {
        $merged[$key] = $value;
      }
    }

    return $merged;
  }
}
