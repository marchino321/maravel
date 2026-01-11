<?php

namespace Core\Classi;

use App\Config;
use App\Debug;
use Core\Classi\Chiamate;
use Core\EventManager;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Core\Helpers\AssetHelper;
use Core\Helpers\Csrf;
use Core\Helpers\LangHelper;
use Core\Lang;
use Core\View\ThemeManager;

if (!defined("CLI_MODE")) {
  defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}
class TwigService
{
  public static function init($menuManager = null): Environment
  {
    $viewsCore = realpath(__DIR__ . '/../../App/Views');
    $loader = new FilesystemLoader($viewsCore);
    /** @var \Twig\Loader\FilesystemLoader $loader */
    $loader->addPath(
      ThemeManager::$basePath,
      'theme'
    );
    $twig = new Environment($loader, [
      'debug' => true,
      'cache' => __DIR__ . '/../cache/twig',
      'auto_reload' => true,
    ]);

    // Plugin views
    $pluginsDir = realpath(__DIR__ . '/../../App/Plugins');
    if (is_dir($pluginsDir)) {
      foreach (scandir($pluginsDir) as $pluginName) {
        if ($pluginName === '.' || $pluginName === '..') continue;
        $pluginViews = $pluginsDir . '/' . $pluginName . '/Views';
        if (is_dir($pluginViews)) {
          $loader->addPath($pluginViews, $pluginName);
          Debug::log("Plugin Twig namespace registrato: $pluginName => $pluginViews", 'PLUGIN');
        }
      }
    }

    $self = new self();

    // -------------------------
    // Tutti i filtri custom compatibili Twig 3
    // -------------------------
    $filters = [
      new TwigFilter('NumFormat', [$self, 'NumFormat']),
      new TwigFilter('pulisciJson', [$self, 'pulisciJson']),
      new TwigFilter('noDate', [$self, 'noDate']),
      new TwigFilter('startForm', [$self, 'startForm']),
      new TwigFilter('Input', [$self, 'Input']),
      new TwigFilter('VarExport', [$self, 'VarExport']),
      new TwigFilter('Convert', [$self, 'Convert']),
      new TwigFilter('GetFilliale', [$self, 'GetFilliale']),
      new TwigFilter('GetSiNo', [$self, 'GetSiNo']),
      new TwigFilter('GetBadgeAcustico', [$self, 'GetBadgeAcustico']),
      new TwigFilter('GetRuoloInsegnante', [$self, 'GetRuoloInsegnante']),
      new TwigFilter('StatusInsegnante', [$self, 'StatusInsegnante']),
      new TwigFilter('GiorniDellaSettimana', [$self, 'GiorniDellaSettimana']),
      new TwigFilter('GetCategoryListini', [$self, 'GetCategoryListini']),
      new TwigFilter('GetLingua', [$self, 'GetLingua']),
      new TwigFilter('GetStatusCliente', [$self, 'GetStatusCliente']),
      new TwigFilter('GetCompleanno', [$self, 'GetCompleanno']),
      new TwigFilter('GetStrumentiServizi', [$self, 'GetStrumentiServizi']),
      new TwigFilter('GetRoomStrumenti', [$self, 'GetRoomStrumenti']),
      new TwigFilter('GetInsegnantiServizi', [$self, 'GetInsegnantiServizi']),
      new TwigFilter('TitologiaDurata', [$self, 'TitologiaDurata']),
      new TwigFilter('checkRoomStrumenti', [$self, 'checkRoomStrumenti']),
      new TwigFilter('GetDettaglioInsegnanteServizio', [$self, 'GetDettaglioInsegnanteServizio']),
      new TwigFilter('GetEntitaServizi', [$self, 'GetEntitaServizi']),
      new TwigFilter('GetRegoleAggiuntive', [$self, 'GetRegoleAggiuntive']),
    ];

    foreach ($filters as $filter) {
      $twig->addFilter($filter);
    }

    $twig->addGlobal('css_files', AssetHelper::getCss());
    $twig->addGlobal('js_files', AssetHelper::getJs());
    $twig->addGlobal('inline_js', AssetHelper::getInlineJs());
    $twig->addGlobal('inline_css', AssetHelper::getInlineCss());
    $twig->addFunction(new \Twig\TwigFunction('__', function (string $key, array $params = []) {
      return \Core\Lang::get($key, $params);
    }));
    $twig->addFunction(new \Twig\TwigFunction('render_assets', function () {
      $html = '';

      // CSS esterni
      foreach (AssetHelper::getCss() as $css) {
        $html .= '<link rel="stylesheet" href="' . $css['url'] . '" id="' . $css['id'] . '">' . "\n";
      }

      // CSS inline
      foreach (AssetHelper::getInlineCss() as $css) {
        $html .= '<style>' . $css . '</style>' . "\n";
      }

      // JS esterni
      foreach (AssetHelper::getJs() as $js) {
        $html .= '<script src="' . $js['file'] . '" id="' . $js['id'] . '"></script>' . "\n";
      }

      // JS inline
      foreach (AssetHelper::getInlineJs() as $js) {
        $html .= '<script>' . $js . '</script>' . "\n";
      }

      return $html;
    }));

    // -------------------------
    // Globali
    // -------------------------
    $twig->addGlobal('baseUrl', '/');
    $twig->addGlobal('assets', Config::$assetsDir);
    $twig->addGlobal('sessione', $_SESSION ?? []);

    $twig->addGlobal('config', [
      'site_name' => Config::$site_name,
      'env' => $_ENV['APP_ENV'] ?? 'dev'
    ]);

    $twig->addGlobal('DEBUG_CONSOLE', Config::$DEBUG_CONSOLE);

    // CSS esterni

    // -------------------------
    // Funzioni globali
    // -------------------------
    $twig->addFunction(new TwigFunction('csrf_token', function () {
      return Csrf::getToken();
    }));
    $twig->addFunction(new TwigFunction('dump', fn($var) => dd($var, true)));
    $twig->addFunction(new TwigFunction('flash_messages', fn() => $_SESSION['flash_mess'] ?? []));
    $twig->addFunction(new TwigFunction('render_console_logs', fn() => Config::$DEBUG_CONSOLE ? Debug::render() : ''));
    $twig->addFunction(new TwigFunction('clear_flash_messages', function () {
      unset($_SESSION['flash_mess']);
    }));


    $twig->addFunction(new \Twig\TwigFunction('lang_flag', fn($l) => LangHelper::flag($l)));
    $twig->addFunction(new \Twig\TwigFunction('lang_label', fn($l) => LangHelper::label($l)));
    $twig->addFunction(new TwigFunction(
      'theme_head_before',
      fn() => ThemeManager::render('head.before'),
      ['is_safe' => ['html']]
    ));

    $twig->addFunction(new TwigFunction(
      'theme_head_after',
      fn() => ThemeManager::render('head.after'),
      ['is_safe' => ['html']]
    ));

    $twig->addFunction(new TwigFunction(
      'theme_body_before',
      fn() => ThemeManager::render('body.before'),
      ['is_safe' => ['html']]
    ));

    $twig->addFunction(new TwigFunction(
      'theme_body_after',
      fn() => ThemeManager::render('body.after'),
      ['is_safe' => ['html']]
    ));

    $twig->addFunction(new TwigFunction(
      'theme_footer_before',
      fn() => ThemeManager::render('footer.before'),
      ['is_safe' => ['html']]
    ));

    $twig->addFunction(new TwigFunction(
      'theme_footer_after',
      fn() => ThemeManager::render('footer.after'),
      ['is_safe' => ['html']]
    ));


    return $twig;
  }
  /**
   * SafeRender: cattura eccezioni Twig e le mostra in modo leggibile
   */
  public static function safeRender(Environment $twig, string $template, array $vars = []): string
  {
    try {
      return $twig->render($template, $vars);
    } catch (\Throwable $e) {
      Debug::log("❌ Errore rendering view: " . $e->getMessage(), "ERROR");
      Debug::log("File: " . $e->getFile() . " Linea: " . $e->getLine(), "ERROR");

      if (Config::$DEBUG_CONSOLE) {
        return "<pre style='color:red; font-weight:bold;'>"
          . "Errore rendering view <b>{$template}</b>\n\n"
          . $e->getMessage() . "\n\n"
          . "File: " . $e->getFile() . " (line " . $e->getLine() . ")"
          . "</pre>";
      }
      // in produzione → niente dettagli
      return "Errore interno, riprovare più tardi.";
    }
  }
  /**
   * Render automatico: se DEBUG_CONSOLE è attivo → safeRender con debug,
   * altrimenti esegue render normale.
   */
  public static function autoRender(Environment $twig, string $template, array $vars = []): string
  {
    if (Config::$DEBUG_CONSOLE) {
      return self::safeRender($twig, $template, $vars);
    }

    return $twig->render($template, $vars);
  }

  // ========= FILTRI =========
  public function NumFormat($val, $formato = '€', $dec = 2): string
  {
    if ($val === "" || $val === null) return "";
    $nmr = number_format((float)$val, $dec, ',', '.');
    return $formato . ' ' . $nmr;
  }

  public function pulisciJson($json)
  {
    if (empty($json) || !is_string($json)) {
      return [];
    }
    $decoded = json_decode($json, true);
    return is_array($decoded) ? $decoded : [];
  }

  public function noDate($string): string
  {
    return ($string && $string !== "0000-00-00 00:00:00")
      ? date('d-m-Y H:i', strtotime($string))
      : 'Nessuna Data';
  }

  public function startForm($metodo, $url = '', $ajaxForm = "true"): string
  {
    if ($metodo === 'inizio') {

      // genera token random se non esiste
      if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
      }
      $csrf = $_SESSION['csrf_token'];
      Debug::log("🎉 Creato evento form.beforeOpen", "EVENTI");
      return '<form class="needs-validation ajax-form" data-ajax="' . $ajaxForm . '" novalidate action="' . $url . '" method="post" enctype="multipart/form-data" id="form-ajax">'
        . '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') . '">';
    }

    Debug::log("🎉 Creato evento form.beforeClose", "EVENTI");
    return '</form>';
  }

  public function Input($label, $name, $placeholder, $require, $valore)
  {
    $richiesto = $require ? 'required' : '';
    $feedBack  = $require ? 'Campo Obbligatorio.' : '';
    return '<label for="' . $name . '" class="form-label">' . $label . '</label>
            <input type="text" name="' . $name . '" class="form-control" id="' . $name . '" placeholder="' . $placeholder . '" value="' . $valore . '" ' . $richiesto . '>
            <div class="invalid-feedback">' . $feedBack . '</div>';
  }

  public function VarExport($array)
  {
    ob_start();
    echo "<pre>";
    print_r($array);
    echo "</pre>";
    return ob_get_clean();
  }

  public function Convert($stringa): string
  {
    return str_replace("\n", "<br />", $stringa);
  }

  public function GetFilliale($id): string
  {
    if ($id == 0) return "In Tutte le Filiali";
    $c = new Chiamate();
    $f = $c->seleziona("tbl_filiali", "idFilialeAutoIncrement", $id);
    return !empty($f) ? $f[0]['labelFilliale'] : "";
  }

  public function GetSiNo($val): string
  {
    return $val ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-ban text-danger"></i>';
  }

  public function GetBadgeAcustico($val): string
  {
    return match ($val) {
      '1' => '<span class="text-warning">Medio</span>',
      '2' => '<span class="text-info">Alto</span>',
      default => '<span class="text-default">Nessuno</span>',
    };
  }

  public function GetRuoloInsegnante($val): string
  {
    return match ($val) {
      1 => '<span class="text-warning">Istruttore Senior</span>',
      2 => '<span class="text-danger">Capo Dipartimento</span>',
      default => '<span class="text-info">Istruttore</span>',
    };
  }

  public function StatusInsegnante($val): string
  {
    return match ($val) {
      '1' => '<span class="text-default">Non Attivo</span>',
      '2' => '<span class="text-danger">OFF</span>',
      default => '<span class="text-success">Attivo</span>',
    };
  }

  public function GiorniDellaSettimana($json): string
  {
    if (empty($json)) return "—";
    $giorni = json_decode($json, true);
    if (!is_array($giorni)) return "—";

    $map = [
      0 => 'Domenica',
      1 => 'Lunedì',
      2 => 'Martedì',
      3 => 'Mercoledì',
      4 => 'Giovedì',
      5 => 'Venerdì',
      6 => 'Sabato'
    ];

    $labels = [];
    foreach ($giorni as $g) {
      if (isset($map[$g])) $labels[] = $map[$g];
    }

    return implode(", ", $labels);
  }

  public function GetCategoryListini($idcat)
  {
    $c = new Chiamate();
    $categorie = $c->seleziona("tel_categorie_listino_prezzi", "idCategoriaListinoPrezzo", $idcat);
    return !empty($categorie) ? '<span class="text-info">' . $categorie[0]['categoria_name'] . '</span>' : '';
  }

  /** Restituisce la lingua con bandiera e nome */
  public function GetLingua($idlingua)
  {
    return match ($idlingua) {
      '1' => '🇮🇹 &nbsp; Italiano',
      '2' => '🇪🇸 &nbsp; Spagnolo',
      '3' => '🇫🇷 &nbsp; Francese',
      '4' => '🇬🇧 &nbsp; Inglese',
      default => ''
    };
  }

  /** Stato cliente: Attivo / Sospeso / Inattivo */
  public function GetStatusCliente($val)
  {
    return match ($val) {
      '1' => '❌ &nbsp; Inattivo',
      '2' => '⏸️ &nbsp; Sospeso',
      default => '✅ &nbsp; Attivo',
    };
  }

  /** Aggiunge 🎉 se oggi è il compleanno dell’utente */
  public function GetCompleanno($dataNascita)
  {
    $data = new \DateTime($dataNascita);
    return $data->format('d-m') === date("d-m") ? "🎉 &nbsp;" : "";
  }

  /** Lista strumenti collegati a un servizio */
  public function GetStrumentiServizi($idServizio)
  {
    $c = new Chiamate();
    $string = "<ul>";
    $join = [
      "tbl_strumenti" => [
        "JOIN" => "INNER",
        "tbl_equipment_service.idStrumento" => "tbl_strumenti.id_strumento_autoIncrement"
      ]
    ];
    $strumento = $c->seleziona("tbl_equipment_service", "idServizio", $idServizio, "", $join);
    if (!empty($strumento)) {
      foreach ($strumento as $value) {
        $string .= "<li>{$value['nome_strumento']} - n°{$value['quantita']}</li>";
      }
    }
    $string .= "</ul>";
    return $string;
  }

  /** Lista room collegate a un servizio */
  public function GetRoomStrumenti($idServizio)
  {
    $c = new Chiamate();
    $string = "<ul>";
    $join = [
      "tbl_room" => [
        "JOIN" => "INNER",
        "ap_servizi_rooms.room_id" => "tbl_room.id_room_autoincrement"
      ]
    ];
    $rooms = $c->seleziona("ap_servizi_rooms", "id_servizio", $idServizio, "", $join);
    if (!empty($rooms)) {
      foreach ($rooms as $r) {
        $string .= "<li>{$r['nome_room']}</li>";
      }
    } else {
      $string = "No Room";
    }
    $string .= "</ul>";
    return $string;
  }

  /** Lista insegnanti assegnati a un servizio */
  public function GetInsegnantiServizi($idServizio)
  {
    $c = new Chiamate();
    $string = "<ul>";
    $join = [
      "tbl_insegnanti" => [
        "JOIN" => "INNER",
        "ap_insegnati_servizi.idInsegnante" => "tbl_insegnanti.idInsegnateAutoINcrement"
      ]
    ];
    $insegnanti = $c->seleziona("ap_insegnati_servizi", "idServizio", $idServizio, "", $join);
    if (!empty($insegnanti)) {
      foreach ($insegnanti as $i) {
        $string .= "<li>{$i['nomeInsegnate']} {$i['cognomeInsegnate']}</li>";
      }
    } else {
      $string = "No Insegnanti";
    }
    $string .= "</ul>";
    return $string;
  }

  /** Converte tipo di durata in testo leggibile (Giorni / Mesi / Ore) */
  public function TitologiaDurata($tipo)
  {
    return match ($tipo) {
      '1' => "Giorni",
      '2' => "Mesi",
      default => "Ore",
    };
  }

  /** Controlla se una room è collegata a un servizio, utile per checkbox precompilata */
  public function checkRoomStrumenti($idRoom, $idServizio)
  {
    if (!$idServizio) return "";
    $c = new Chiamate();
    $check = $c->seleziona("ap_servizi_rooms", "room_id", $idRoom, " AND id_servizio = $idServizio");
    return !empty($check) ? "checked" : "";
  }

  /** Restituisce i dettagli di un insegnante per un servizio */
  public function GetDettaglioInsegnanteServizio($idInsegnante, $idServizio)
  {
    if ($idServizio <= 0) return [];
    $c = new Chiamate();
    $check = $c->seleziona("ap_insegnati_servizi", "idInsegnante", $idInsegnante, " AND idServizio = $idServizio");
    return !empty($check) ? $check[0] : [];
  }

  /** Lista entità collegate a un servizio */
  public function GetEntitaServizi($idServizio)
  {
    $c = new Chiamate();
    $string = "<ul>";
    $join = [
      "tbl_entita" => [
        "JOIN" => "INNER",
        "tbl_service_entity.idEntita" => "tbl_entita.idEntitaAutoIncrement"
      ]
    ];
    $entita = $c->seleziona("tbl_service_entity", "idServizioPadre", $idServizio, "", $join);
    if (!empty($entita)) {
      foreach ($entita as $e) {
        $string .= "<li>{$e['ragioneSocial']}</li>";
      }
    } else {
      $string = "No Entità";
    }
    $string .= "</ul>";
    return $string;
  }

  /** Lista regole aggiuntive legate a una regola principale (con percentuali e tempi) */
  public function GetRegoleAggiuntive($idRegola)
  {
    $c = new Chiamate();
    $string = "<ul>";
    $regole = $c->seleziona("ap_regole_add", "idMainRegola", $idRegola);
    if (!empty($regole)) {
      $tipo = match ($regole[0]['tipologiaTempo']) {
        '1' => "Ore",
        '2' => "Giorni",
        default => "Minuti",
      };
      foreach ($regole as $r) {
        $string .= "<li>{$r['tipologiaPenalita']} - <span class='text-danger'>{$r['percentualeCharge']}%</span> ENTRO <span class='text-warning'>{$r['minutiSetting']} $tipo</span></li>";
      }
    } else {
      $string = "<li>No Regole Aggiuntive</li>";
    }
    $string .= "</ul>";
    return $string;
  }
}
