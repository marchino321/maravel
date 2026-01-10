<?php

declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Autoload Composer
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
  die("❌ Errore critico<br>Manca <code>vendor/autoload.php</code>. Esegui <code>composer install</code> nella root public_html.");
}
require __DIR__ . '/vendor/autoload.php';

use App\Install\Installer;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;

$installer = new Installer();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  echo "ricevuto post <br />";
  switch ($action) {
    case 'check':
      header('Content-Type: application/json');
      echo json_encode($installer->checkRequirements(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
      exit;

    case 'install':

      //header('Content-Type: application/json');
      echo "Inizio Installazione DB<br />";
      $result = $installer->installDatabase([
        'db_host'    => $_POST['db_host'] ?? 'localhost',
        'db_name'    => $_POST['db_name'] ?? '',
        'db_user'    => $_POST['db_user'] ?? '',
        'db_pass'    => $_POST['db_pass'] ?? '',
        'site_name'  => $_POST['site_name'] ?? 'Gestionale',
        'abs_key'    => $_POST['abs_key'] ?? '',
        'logo_app'   => $_POST['logo_app'] ?? '',
        'header_api' => $_POST['header_api'] ?? ''
      ]);
      echo "Fine InstallazioneDB <br />";
      $configPath = dirname(__DIR__, 2) . '/ConfigFiles/config.local.json';
      var_export($result);
      if ($result['status'] === 'ok') {
        \Core\Classi\Flash::AddByKey('db.install');
        echo "Adesso redirect <br />";
        header("Location: /", true, 303);
      } else {
        // 🔥 rollback installazione
        if (file_exists($configPath)) {
          unlink($configPath);
        }
        \Core\Classi\Flash::AddByKey('db.crash');
        header('Location: /install');
      }
      exit;
  }
}

// --------------------
// 🔹 Carica la view
// --------------------
$reqs = $installer->checkRequirements();

$viewsPaths = [
  __DIR__ . '/App/Views/Install',
  __DIR__ . '/App/Views',  // layout.html e altre views globali
];

$loader = new \Twig\Loader\FilesystemLoader($viewsPaths);
$twig   = new \Twig\Environment($loader, [
  'debug' => true,
  'cache' => false,
]);

echo $twig->render('index.html', [
  'php_version'   => PHP_VERSION,
  'reqs'          => $reqs,
  'ssl_enabled'   => $reqs['ssl']['enabled'],
  'has_composer'  => $reqs['composer']['installed']
]);
