<?php

namespace App\Controllers\Private;

use App\Config;
use App\Debug;
use Core\Auth;
use Core\Classi\Flash;
use Core\Controller;
use Core\Helpers\FormHelper;
use Core\MigrationManager;
use Core\Plugin;
use Core\Services\CliCommandService;
use Core\UpdateManager;
use Core\View\TwigManager;
use Core\View\MenuManager;
use Core\Services\SystemInfoService;


if (!defined("CLI_MODE")) {
  defined(Config::$ABS_KEY) || exit('Accesso diretto non consentito.');
}

class SuperAdmin extends Controller
{

  private string $configFile;

  public function __construct(TwigManager $twigManager, MenuManager $menuManager)
  {
    $this->configFile = Config::$configDir . '/config.local.json';
    parent::__construct($twigManager);

    // Usa il MenuManager globale passato dal bootstrap
    $this->menuManager = $menuManager;
  }

  public function Index(...$params): void
  {
    if (isset($_GET['update'])) {
      Flash::AddByKey('system.debug');
    }


    $service = new SystemInfoService();

    $data = $service->getSystemInfo();
    $data['debug'] = $service->isDebugEnabled() ? 'true' : 'false';
    $data['log']   = $service->listLogs();


    $updateFile = Config::$baseDir . '/ConfigFiles/update.json';

    $coreVersion = '';
    $lastUpdate = '';
    if (file_exists($updateFile)) {
      $updateData = json_decode(file_get_contents($updateFile), true);
      $coreVersion = $updateData['core_version'] ?? '';
      $lastUpdate = $updateData['last_update'] ?? '';
    }
    $debugOutput = Debug::getLogs();
    $plugin_count = Plugin::count();
    $pluginList = Plugin::getPlugins();
    //var_export($debugOutput);
    echo $this->twigManager->getTwig()->render(
      'Private/SuperAdmin/index.html',
      [
        'Titolo'       => 'Setting Super Admin',
        'data'         => $data,
        'php_sapi'     => php_sapi_name(),
        'core_version' => $coreVersion,
        'last_update'  => $lastUpdate,
        'debug_output' => $debugOutput,
        'plugin_count' => $plugin_count,
        'pluginList'   => $pluginList,
      ]
    );
  }
  public function togglePluginAjax(): void
  {

    if (!Auth::checkSuperAdmin()) {
      $this->jsonResponse(false, ['error' => 'Accesso negato']);
      return;
    }

    $plugin = $_POST['plugin'] ?? null;
    $active = isset($_POST['active']) && $_POST['active'] == 1;

    $configPath = Config::$pluginDir . "/{$plugin}/config.json";

    if (!file_exists($configPath)) {
      $this->jsonResponse(false, ['error' => 'Config plugin non trovata']);
      return;
    }

    $config = json_decode(file_get_contents($configPath), true);
    $config['active'] = $active;

    file_put_contents(
      $configPath,
      json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

    Debug::log(
      "Plugin {$plugin} " . ($active ? 'attivato' : 'disattivato'),
      'PLUGIN'
    );

    $this->jsonResponse(true, [
      'status' => 'ok',
      'reload' => true
    ]);
  }
  public function ToggleDebugAjax(): void
  {

    if (!Auth::checkSuperAdmin()) {
      $this->jsonResponse(false, ['error' => 'Accesso negato']);
      return;
    }

    $debug = $_POST['debug'] ?? null;
    $debug = $debug === '1';

    $configPath = Config::$baseDir . '/ConfigFiles/config.local.json';

    if (!file_exists($configPath)) {
      $this->jsonResponse(false, ['error' => 'Config non trovata']);
      return;
    }

    $config = json_decode(file_get_contents($configPath), true);

    $config['DEBUG_CONSOLE'] = $debug;

    file_put_contents(
      $configPath,
      json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

    // aggiorna config runtime
    Config::$DEBUG_CONSOLE = $debug;
    Flash::AddByKey('system.debug');
    $this->jsonResponse(true, [
      'status' => 'ok',
      'debug'  => $debug
    ]);
  }

  public function Documentazione(...$params): void
  {
    echo $this->twigManager->getTwig()->render('Private/SuperAdmin/documentazione-base.html', [
      'Titolo' => 'Setting Super Admin',

    ]);
  }
  public function DoExport(...$params)
  {
    $bm = new \Core\BackupManager();
    $tabelle = [];
    // dd($_POST);
    if ($_POST['tabelle_incluse'] !== "") {
      $tabelle = explode(",", $string = preg_replace('/\s+/', '', $_POST['tabelle_incluse']));
    }
    $zipPath = $bm->exportProject(true, $tabelle);

    echo $this->twigManager->getTwig()->render('Private/SuperAdmin/export.html', [
      'Titolo' => 'Esporta Progetto',
      'zipPath' => $zipPath,
    ]);
  }
  public function aggiornamenti(): void
  {
    $updateManager = new UpdateManager();
    $coreInfo = $updateManager->checkForUpdates();

    // Per ora mock plugin
    $plugins = [
      ['name' => 'TestPlugin', 'current' => '1.0.0', 'latest' => '1.0.0', 'update_available' => false]
    ];

    $logFile = Config::$logDir . "/update.log";
    $log = file_exists($logFile) ? file_get_contents($logFile) : "Nessun log disponibile.";
    $messaggio = "";
    $messaggio = '';

    if (isset($_GET['ok'])) {
      $messaggio = '
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        ✅ Aggiornamento sistema completato con successo.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
    }

    if (isset($_GET['err'])) {
      $messaggio = '
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        ❌ Si è verificato un errore durante l’operazione.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
    }
    echo $this->twigManager->getTwig()->render("Private/SuperAdmin/aggiornamenti.html", [
      "core" => $coreInfo,
      "plugins" => $plugins,
      "log" => $log,
      "Titolo" => "Aggiornamento Systema",
      "messaggio" => $messaggio
    ]);
  }

  public function applyCoreUpdate(): void
  {
    $updateManager = new UpdateManager();
    $ok = $updateManager->applyUpdate();
    // dd($ok);
    if ($ok) {
      Debug::log("✅ Core aggiornato con successo", "UPDATE");
      header("Location: /private/super-admin/aggiornamenti?ok=1");
    } else {
      Debug::log("❌ Nessun aggiornamento core applicato", "UPDATE");
      header("Location: /private/super-admin/aggiornamenti?err=1");
    }
    exit;
  }
  function getSimpleOS(): string
  {
    $full = php_uname(); // es. "Linux server.arte58studios.it 6.1.0-10-amd64 #1 SMP ... Debian 6.1.37-1 (2023-07-03) x86_64"

    // Prendiamo solo la prima parte (OS + hostname)
    preg_match('/^(Linux\s+\S+)/i', $full, $matches);
    $osHost = $matches[1] ?? 'Linux';

    // Cerchiamo il nome della distribuzione (Debian, Ubuntu, CentOS, ecc.)
    if (preg_match('/(Debian|Ubuntu|CentOS|Red\s?Hat|Fedora|Alpine)/i', $full, $match)) {
      $distro = $match[1];
    } else {
      $distro = '';
    }

    return trim($osHost . ' ' . $distro);
  }

  /**
   * Legge config.local.json
   */
  private function readConfig(): array
  {
    if (!file_exists($this->configFile)) {
      return [];
    }
    $json = file_get_contents($this->configFile);
    return json_decode($json, true) ?: [];
  }


  public function MigrazioniDatabase(): void
  {
    $mm = new MigrationManager();

    $sqlDir = Config::$baseDir . '/MigrationsSQL';
    $sqlFiles = [];

    if (is_dir($sqlDir)) {
      $sqlFiles = array_values(array_filter(
        scandir($sqlDir),
        fn($f) => str_ends_with($f, '.sql')
      ));
    }

    echo $this->twigManager->getTwig()->render(
      'Private/SuperAdmin/migrazioni_db.html',
      [
        'Titolo'        => 'Migrazioni Database',
        'migrations'    => $mm->getAllMigrations(),
        'cliCommand'    => 'php cli.php migrate',
        'sqlExportPath' => '/MigrationsSQL',
        'sqlFiles'      => $sqlFiles
      ]
    );
  }

  public function RunCliMigrate(): void
  {
    $svc = new CliCommandService();
    $result = $svc->runMigrate();

    $this->jsonResponse(
      $result['success'],
      [
        'output' => $result['output']
      ],
      $result['success'] ? '' : 'Errore esecuzione CLI'
    );
  }

  public function ExportDbSchema(): void
  {
    $mm = new MigrationManager();
    $data = '';
    if (isset($_GET['tipo_export'])) {
      $data = '--no-data';
    }
    $file = $mm->exportCurrentSchema($data);

    Flash::AddMex("Snapshot DB creato: {$file}", Flash::SUCCESS);
    header("Location: /private/super-admin/migrazioni-database", true, 303);
    exit;
  }

  public function EliminaSnapshotSql(): void
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      header('Location: /private/super-admin/migrazioni-database', true, 303);
      exit;
    }

    $file = $_POST['file'] ?? '';
    $file = basename($file);

    $baseDir = realpath(Config::$baseDir . '/MigrationsSQL');
    $path    = realpath($baseDir . '/' . $file);

    if ($path && str_starts_with($path, $baseDir) && str_ends_with($path, '.sql')) {
      unlink($path);
      Flash::AddMex("Snapshot {$file} eliminato", Flash::SUCCESS);
    } else {
      Flash::AddMex("File non valido", Flash::DANGER);
    }

    header("Location: /private/super-admin/migrazioni-database", true, 303);
    exit;
  }

  public function EsportaProgetto(...$params): void
  {


    echo $this->twigManager->getTwig()->render('Private/SuperAdmin/export.html', [
      'Titolo' => 'Esporta Progetto'
    ]);
  }
  public function TestAjax()
  {
    Debug::log("📤 Test Ajax Messaggi", 'AJAX');
    echo json_encode([
      'status' => 'ok',
      'file'   => "",
      'logs'   => Debug::getLogs()
    ]);
    return;
  }
  public function Snipped(...$params)
  {
    echo $this->twigManager->getTwig()->render('Docs/Index.html', [
      'Titolo' => 'Esporta Progetto'
    ]);
  }
  public function BuildCoreAjax(): void
  {
    if (!\Core\Auth::checkSuperAdmin()) {
      http_response_code(403);
      echo json_encode([
        'status' => 'error',
        'output' => 'Access denied'
      ]);
      return;
    }

    $version = $_POST['version'] ?? null;

    if (!$version) {
      $this->jsonResponse(false, [
        'output' => 'Missing version'
      ]);
      return;
    }

    $cliPath = Config::$baseDir . '/MyFiles/build-core-json.php';

    if (!file_exists($cliPath)) {
      $this->jsonResponse(false, [
        'output' => 'CLI file not found: ' . $cliPath
      ]);
      return;
    }

    $cmd = sprintf(
      'php %s %s 2>&1',
      escapeshellarg($cliPath),
      escapeshellarg($version)
    );

    $output = shell_exec($cmd);

    if ($output === null) {
      $this->jsonResponse(false, [
        'output' => 'Command execution failed'
      ]);
      return;
    }

    $this->jsonResponse(true, [
      'status' => 'ok',
      'output' => trim($output)
    ]);
  }
  public function SwitchTheme(...$params)
  {
    $theme = $_POST['theme'] ?? null;

    // 🔴 tema mancante o vuoto
    if (!$theme || !is_string($theme)) {
      Flash::AddMex('Tema non valido');
      header('Location: /private/super-admin', true, 303);
      exit;
    }

    $themeDir = Config::$baseDir . '/App/Theme/' . $theme;

    if (!is_dir($themeDir)) {
      Flash::AddMex('Il tema selezionato non esiste');
      exit;
    }

    $configFile = Config::$baseDir . '/ConfigFiles/config.local.json';

    if (!file_exists($configFile)) {
      Flash::AddMex('File di configurazione non trovato');
      exit;
    }

    // 🔒 lock del file
    $fp = fopen($configFile, 'c+');
    if (!$fp) {
      Flash::AddMex('Impossibile aprire il file di configurazione');
      exit;
    }

    flock($fp, LOCK_EX);

    $json = stream_get_contents($fp);
    $config = json_decode($json, true);

    if (!is_array($config)) {
      flock($fp, LOCK_UN);
      fclose($fp);
      Flash::AddMex('Configurazione JSON non valida');
      exit;
    }

    // ✅ aggiorna il tema
    $config['THEME'] = $theme;

    // ✍️ riscrive il file
    ftruncate($fp, 0);
    rewind($fp);
    fwrite(
      $fp,
      json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    Flash::AddMex("Tema $theme attivato correttamente", Flash::SUCCESS, "Thema Salvato");

    header('Location: /private/super-admin', true, 303);
    exit;
  }
}
