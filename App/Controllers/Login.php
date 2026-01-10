<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config;
use App\Models\User;
use Core\Controller;
use Core\Classi\Flash;
use App\Debug;
use Core\EventManager;

if (!defined("CLI_MODE")) {
  defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}

/**
 * Controller Login
 *
 * - Mostra form di login
 * - Gestisce autenticazione
 * - Logout sicuro
 *
 * PHP 8.3
 * By Marco Dattisi
 */
class Login extends Controller
{
  /**
   * Mostra il form di login
   */

  public function index(): void
  {


    echo $this->twigManager->getTwig()->render('Login/index.html', [
      'titolo'     => 'Login',
      'LOGIN'      => true,
    ]);
  }

  /**
   * Esegue l’autenticazione
   */
  public function autentica(): void
  {
    $email    = $_POST['email']    ?? '';
    $password = $_POST['password'] ?? '';


    Debug::log("Tentativo login da {$email}", "SISTEMA");

    if (empty($email) || empty($password)) {
      Flash::AddMex("Email o password mancanti", Flash::DANGER, "Login");
      Debug::log("Login fallito per {$email} - campi vuoti", "SISTEMA");
      header("Location: /login");
      exit;
    }

    // Istanzia il modello User
    $user = new User([
      'email'    => $email,
      'password' => $password
    ]);

    // Verifica login
    if ($user->loginUser()) {

      Debug::log("Login riuscito per {$email}", "SISTEMA");
      EventManager::dispatch("user.login", $_SESSION[Config::$SESSION_USER_KEY]);
      // var_export($_SESSION);
      if (!empty($_SESSION['redirect_after_login'])) {
        $redirect = $_SESSION['redirect_after_login'];
        unset($_SESSION['redirect_after_login']); // pulisci subito
        // $this->jsonResponse(true, [

        //     "redirect" => "/private/dashboard"
        // ]);

        header("Location: /" . ltrim($redirect, '/'), true, 303);
        exit;
      }
      // die;
      // $this->jsonResponse(true,  [

      //     "redirect" => "/private/dashboard"
      // ]);
      header("Location: /private/dashboard");
      exit;
    } else {
      Flash::AddMex("Credenziali non valide", Flash::DANGER, "Login");
      Debug::log("Login non riuscito per {$email}", "SISTEMA");
      // $this->jsonResponse(false,  [

      //     "redirect" => "/private/dashboard"
      // ]);

      header("Location: /private/dashboard");
      exit;
    }
  }
}
