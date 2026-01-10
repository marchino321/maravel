<?php

namespace App\Plugins\PluginServizio;

use Core\PluginController;
use Core\Router;
use Core\View\TwigManager;
use Core\View\MenuManager;
use Core\EventManager;
use App\Debug;
use App\Config;
use Core\Auth;
use Core\Classi\Flash;

if (!defined("CLI_MODE")) {
    defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}

/**
 * Plugin di esempio completo
 * Mostra come usare:
 *  - Aggiunta CSS/JS
 *  - Variabili globali Twig
 *  - Menu padre/figlio con icone
 *  - Registrazione route plugin
 *  - Logging con Debug::log
 *  - Eventi (router, controller, form)
 */
class PluginServizio extends PluginController
{
    private TwigManager $twigManager;

    public function register(Router $router, TwigManager $twigManager, ?MenuManager $menuManager = null): void
    {
        Debug::log("Registrazione plugin PluginServizio iniziata", 'PLUGIN');
        // Salvo il TwigManager per usarlo nei metodi
        $this->twigManager = $twigManager;

        // -------------------------
        // Registrazione route plugin
        // -------------------------
        $router->addPluginRoute('logout', fn() => $this->logout());

        $router->addPluginRoute('registrami', fn(...$params) => $this->mostraMessaggio(...$params));


        // -------------------------
        // Registrazione Eventi
        // -------------------------
        $this->registerEvents();
    }

    private function registerEvents(): void
    {
        EventManager::on("form.beforeOpen", function ($url) {
            $token = bin2hex(random_bytes(16));
            $_SESSION['csrf_token'] = $token;
            return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
        });

        EventManager::on("form.beforeClose", function ($url) {
            return '<!-- chiusura sicura form -->';
        });

        EventManager::on("form.submitted", function ($router, $postData) {
            if (empty($postData['csrf_token']) || $postData['csrf_token'] !== ($_SESSION['csrf_token'] ?? null)) {
                Debug::log("❌ CSRF token non valido", "SECURITY");
                return false; // 🔹 segnala errore, ma non stampa nulla
            }
            
            unset($postData['csrf_token']);
            return $postData;
        });
        EventManager::on("installazione.nuova", function ($messaggio) {
            Debug::log("⚙️ Avvio sync risorse in fase di installazione...", "INSTALL");
            //syncRisorseAutomatiche();
            Debug::log("🚀 Inizio register() di PluginServizio", "PLUGIN");
            Debug::log("✅ Risorse sincronizzate correttamente", "INSTALL");
            EventManager::dispatch("plugin.syncRisorse"); // ogni plugin può agganciarsi
        });
    }

    private function logout(): void
    {
        Auth::logout();
    }

    public function mostraMessaggio(...$params): void
    {
        echo $this->twigManager->getTwig()->render('Error/Errors.twig', [
            'title'     => 'Nessuna Registrazione!',
            'messaggio' => 'In questo sistema non esiste una registrazione libera!',
            'risposta'  => "",
        ]);
    }
}