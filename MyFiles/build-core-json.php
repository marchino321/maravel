<?php

use App\Config;

require_once __DIR__ . '/../vendor/autoload.php';

error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');
$isCli = (PHP_SAPI === 'cli');

if ($isCli) {
  $version = $argv[1] ?? '1.0.0';
} else {
  $version = $_POST['version'] ?? '1.0.0';
}

// Normalizza
$version = trim($version);


if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
  $version = '1.0.0';
}



$patches = [
  "Creazione Sistema MVC custom + API core v0.0.0",
  "Sistema API interno centralizzato v0.1.0",
  "Sistema Plugin custom estendibile v0.2.0",
  "Sistema Permessi e Menu dinamico v0.3.0",
  "Sistema Hook ed Eventi v0.4.0",
  "Sistema Update centralizzato v0.5.0",
  "Sistema AjaxHelper v0.6.0",
  "Sistema AjaxHelper avanzato v0.7.0",
];

$patches = array_reverse($patches);

$isCli = (php_sapi_name() === 'cli');
$host  = $isCli ? Config::$LinkUpdate : ($_SERVER['HTTP_HOST'] ?? 'localhost');
$baseUrl = "https://{$host}";

$baseDir    = realpath(__DIR__ . '/../');
$coreDir    = realpath($baseDir . '/Core');
$myFilesDir = realpath($baseDir . '/MyFiles');

if (!$coreDir || !$myFilesDir) {
  exit("❌ Directory Core o MyFiles non trovata\n");
}

$coreJson = [
  "latest_core"      => $version,
  "security_patches" => $patches,
  "files"            => [],
  "zip"              => null
];

/**
 * 📦 Aggiunge un file al core.json + MyFiles
 */
function addFile(string $absolutePath, string $relativePath, array &$coreJson, string $myFilesDir, string $baseUrl)
{
  $txtName = str_replace(['/', '\\'], '_', $relativePath) . '.txt';
  $txtPath = $myFilesDir . DIRECTORY_SEPARATOR . $txtName;

  file_put_contents($txtPath, file_get_contents($absolutePath));

  $coreJson['files'][] = [
    'url'    => $baseUrl . '/MyFiles/' . $txtName,
    'path'   => $relativePath,
    'sha256' => hash_file('sha256', $absolutePath)
  ];
}

/**
 * 📂 Scansione directory ricorsiva
 */
function scanDirRecursive(
  string $dir,
  string $basePath,
  array &$coreJson,
  string $myFilesDir,
  string $baseUrl
) {
  $it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
  );

  foreach ($it as $file) {

    if ($file->isDir()) {
      continue;
    }

    // ✅ consenti solo php e html
    $ext = strtolower($file->getExtension());
    if (!in_array($ext, ['php', 'html'], true)) {
      continue;
    }

    // ❌ escludi cache
    if (str_contains(
      $file->getPathname(),
      DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR
    )) {
      continue;
    }

    $relative = ltrim(
      str_replace($basePath, '', $file->getPathname()),
      '/\\'
    );

    addFile(
      $file->getPathname(),
      $relative,
      $coreJson,
      $myFilesDir,
      $baseUrl
    );
  }
}

/* ======================================================
 * 1️⃣ CORE
 * ====================================================== */
scanDirRecursive($coreDir, $baseDir, $coreJson, $myFilesDir, $baseUrl);

/* ======================================================
 * 2️⃣ FILE SINGOLI DI FRAMEWORK
 * ====================================================== */
$fixedFiles = [
  'install.php',
  'cli.php',
  'index.php',
  'ConfigFiles/bootstrap.php'

];

foreach ($fixedFiles as $file) {
  $path = $baseDir . '/' . $file;
  if (file_exists($path)) {
    addFile($path, $file, $coreJson, $myFilesDir, $baseUrl);
  }
}

/* ======================================================
 * 3️⃣ DIRECTORY FRAMEWORK FISSE
 * ====================================================== */
$fixedDirs = [
  'App/Install',
  'App/Controllers/Private/SuperAdmin',
  'App/Views/Private/SuperAdmin',
  'App/Views/Docs',
  'App/Views/Error',
  'App/Plugins/HeaderMenu',
  'App/Plugins/PluginServizio',
  'App/Lang',
];

foreach ($fixedDirs as $dir) {
  $full = $baseDir . '/' . $dir;
  if (is_dir($full)) {
    scanDirRecursive($full, $baseDir, $coreJson, $myFilesDir, $baseUrl);
  }
}

/* ======================================================
 * 4️⃣ SCRITTURA core.json
 * ====================================================== */
file_put_contents(
  __DIR__ . '/core.json',
  json_encode($coreJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

echo "✅ Framework core scansionato, MyFiles aggiornato, core.json generato\n";
