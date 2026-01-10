<?php

namespace Core;

use App\Config;
use Core\Classi\Flash;

class Auth
{
    /**
     * Controlla se l'utente è loggato
     */
    public static function check(): bool
    {
        return !empty($_SESSION[Config::$SESSION_USER_KEY]);
    }

    /**
     * Controlla se l'utente è Super Admin
     */
    public static function checkSuperAdmin(): bool
    {
        if(isset($_SESSION[Config::$SESSION_USER_KEY] ) && $_SESSION[Config::$SESSION_USER_KEY] == Config::$ID_SUPER_ADMIN){
            return true;
        }
        return false;
        
    }
    /**
     * Restituisce l'ID utente loggato
     */
    public static function id(): ?int
    {
        return $_SESSION[Config::$SESSION_USER_KEY] ?? null;
    }

    /**
     * Restituisce l'intera sessione utente
     */
    public static function user(): array
    {
        return [
            'id'                 => $_SESSION[Config::$SESSION_USER_KEY] ?? null,
            'nome'               => $_SESSION['nomeUtente'] ?? null,
            'cognome'            => $_SESSION['cognomeUtente'] ?? null,
            'email'              => $_SESSION['emailUtente'] ?? null,
            'permesso'           => $_SESSION['descrizionePermesso'] ?? null,
            'foto_profilo'       => $_SESSION['avatar_utente'] ?? null,
            'default_setting' => $_SESSION['DefaultSetting']
                ?? json_encode([
                    "mode"    => "light",
                    "width"   => "fluid",
                    "menuPosition" => "fixed",
                    "sidebar" => ["color" => ["dark"]],
                    "topbar"  => ["color" => ["dark"]],
                    "showRightSidebarOnPageLoad" => true
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * Restituisce il ruolo dell'utente
     */
    public static function role(): ?string
    {
        return $_SESSION['descrizionePermesso'] ?? 'nessuno';
    }

    /**
     * Logout → cancella tutta la sessione
     */
    public static function logout(): void
    {
        $userId = $_SESSION[Config::$SESSION_USER_KEY] ?? null;

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_unset();
            session_destroy();
            session_write_close();
        }

        setcookie('logedIn', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        EventManager::dispatch("user.logout", $userId);
        //Flash::AddMex("Logout effettuato", Flash::SUCCESS, "Login");

        header("Location: /login");
        exit; // importante per bloccare codice dopo
    }
    
}
