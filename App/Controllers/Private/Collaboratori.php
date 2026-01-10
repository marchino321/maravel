<?php

namespace App\Controllers\Private;


use Core\Controller;
use Core\View\TwigManager;
use Core\View\MenuManager;
use Core\Components\DynamicTable;
use Core\Helpers\FormHelper;
use App\Config;
use App\Models\ModelCollaboratori;
use Core\Classi\Flash;
use Core\Classi\GetHeaders;
use Core\Helpers\UploadHelper;

if (!defined("CLI_MODE")) {
    defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}
class Collaboratori extends Controller
{
    //private MenuManager $menuManager;

   
    function ElencoCollaboratori(...$params)
    {
        
        $ritorno['Titolo'] = "Elenco Collaboratori";
        $ritorno['Elenco'] = ModelCollaboratori::GetAllCollaboratori();
        $ritorno['Link'] = "/private/collaboratori/aggiungi-modifica-collaboratore";
        $ritorno['Label'] = "Aggiungi Collaboratore";
        echo $this->twigManager->getTwig()->render('Private/Collaboratori/elenco-collaboratori.html', $ritorno);
    }
    function AggiungiModificaCollaboratore(...$params)
    {
        $ritorno['Titolo'] = "Aggiungi Collaboratore";
        $ritorno['Form'] = "/private/Collaboratori/SalvaCollaboratore";
        $passwordlUtente = randomPassword(16);
        $collaboratoreData = [];
        if(isset($params[1])){

            $collaboratoreData = ModelCollaboratori::GetCollaboratoreByID($params[1]);
            $ritorno['Titolo'] = "Modifica Collaboratore - " . $collaboratoreData['nomeUtente'] . " " . $collaboratoreData['cognomeUtente'];
            $passwordlUtente = "";
            $ritorno['Form'] .= "/" . $params[1];
            $ritorno['avatar'] = $collaboratoreData['avatar_utente'];
        }

        $ritorno['nomeUtente'] = FormHelper::field('nomeUtente', [
            'type' => 'text',
            'label' => 'Nome Collaboratore',
            'placeholder' => 'Inserisci il nome del collaboratore',
            'required' => true
        ], isset($collaboratoreData['nomeUtente']) ? $collaboratoreData['nomeUtente'] : "");

        $ritorno['cognomeUtente'] = FormHelper::field('cognomeUtente', [
            'type' => 'text',
            'label' => 'Cognome Collaboratore',
            'placeholder' => 'Inserisci il cognome del collaboratore',
            'required' => true
        ], isset($collaboratoreData['cognomeUtente']) ? $collaboratoreData['cognomeUtente'] : "");


        $ritorno['emailUtente'] = FormHelper::field('emailUtente', [
            'type' => 'email',
            'label' => 'Email Collaboratore',
            'placeholder' => 'Inserisci l\'email del collaboratore',
            'required' => true
        ], isset($collaboratoreData['emailUtente']) ? $collaboratoreData['emailUtente'] : "");

        $ritorno['passwordUtente'] = FormHelper::field('passwordUtente', [
            'type' => 'text',
            'label' => 'Password Collaboratore',
            'placeholder' => 'Inserisci la password del collaboratore',
            'required' => isset($params[1]) ? false : true
        ], $passwordlUtente);

        $ritorno['Upload'] = FormHelper::fileUpload('fotoProfilo', 'Trascina qui la nuova foto profilo');

        echo $this->twigManager->getTwig()->render('Private/Collaboratori/aggiungi-collaboratori.html', $ritorno);
    }

    function SalvaCollaboratore(...$params)
    {

        // Recupera l'email dal POST
        $email = $_POST['emailUtente'] ?? '';

        // 🔹 Validazione lato server
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Errore: email non valida
            Flash::AddByKey("no.mail");
            return $this->jsonResponse(false, [
                'error' => 'Indirizzo email non valido'
            ]);
        }
        

        $salvato = ModelCollaboratori::InserisciAggiornaCollaboratore($params[1] ?? 0, $_POST);

        if ($salvato) {
            
            return $this->jsonResponse(true, [
                'redirect' => '/private/collaboratori/elenco-collaboratori'
            ]);
        } else {
            Flash::AddByKey("system.error");
            return $this->jsonResponse(false, [
                'error' => 'Salvataggio non riuscito'
            ]);
        }
    }
}