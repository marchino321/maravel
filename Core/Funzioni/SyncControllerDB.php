<?php

use App\Debug;
use Core\Classi\Chiamate;

function syncRisorseAutomatiche(string $directory = __DIR__ . "/../../App/Controllers/Private"): void
{
    $c = new Chiamate();

    // Risorse attuali dal DB
    $risorseDb = $c->seleziona("ap_risorse", 1, 1);
    $mapDb = [];
    foreach ($risorseDb as $r) {
        $mapDb[$r['nomeRisorsa']] = $r;
    }

    foreach (glob($directory . "/*.php") as $file) {
        $nomeFile   = basename($file, ".php");
        $className  = "App\\Controllers\\Private\\{$nomeFile}";

        if (!class_exists($className) ) {
            require_once $file;
        }
        if (!class_exists($className)) {
            Debug::log("❌ Classe non trovata: {$className}", "PERMESSI");
            continue;
        }

        $refClass = new \ReflectionClass($className);
        foreach ($refClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== $className) continue;
            if (in_array($method->name, ["__construct", "SuperAdmin"])) continue; // 👈 IGNORA SuperAdmin

            $nomeRisorsa = $nomeFile . "@" . ucfirst($method->name);

            $descrizione = $method->getDocComment()
                ? trim(preg_replace('/\s*\*\s?/', ' ', str_replace(["/**", "*/"], "", $method->getDocComment())))
                : "Risorsa auto-generata per {$nomeRisorsa}";

            if (!isset($mapDb[$nomeRisorsa])) {
     
                    //SuperAdmin
                    if (!str_contains($nomeRisorsa, 'SuperAdmin')) {
                    $c->salva("ap_risorse", [
                        "nomeRisorsa" => $nomeRisorsa,
                        "tipoRisorsa" => "controller",
                        "descrizione" => $descrizione
                    ]);
                    Debug::log("➕ Inserita nuova risorsa: {$nomeRisorsa}", "PERMESSI");
                    }

                  
                
            } else {
                // Aggiorna descrizione se differente
                $dbDesc = trim((string)$mapDb[$nomeRisorsa]['descrizione']);
                if ($dbDesc !== $descrizione) {
                    $c->aggiorna("ap_risorse", [
                        "descrizione" => $descrizione
                    ], "idRisorsa", $mapDb[$nomeRisorsa]['idRisorsa']);
                    Debug::log("✏️ Aggiornata descrizione risorsa: {$nomeRisorsa}", "PERMESSI");
                }
            }
        }
    }

    Debug::log("✅ Sincronizzazione risorse completata", "PERMESSI");
}