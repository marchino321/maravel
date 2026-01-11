<?php

use App\Config;

require_once __DIR__ . '/../vendor/autoload.php';

error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

$isCli = (PHP_SAPI === 'cli');

$version = $isCli
  ? ($argv[1] ?? '1.0.0')
  : ($_POST['version'] ?? '1.0.0');

$version = trim($version);

if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
  $version = '1.0.0';
}

/* ======================================================
 * 📌 PATCH NOTES
 * ====================================================== */
$patches = [
  "Creazione Sistema MVC custom + API core v0.0.0",
  "Sistema API interno centralizzato v0.1.0",
  "Sistema Plugin custom estendibile v0.2.0",
  "Sistema Permessi e Menu dinamico v0.3.0",
  "Sistema Hook ed Eventi v0.4.0",
  "Sistema Update centralizzato v0.5.0",
  "Sistema AjaxHelper v0.6.0",
  "Sistema AjaxHelper avanzato v0.7.0",
  "Sistema Traduzione avanzato v0.8.0",
  "Sistema di theme custom v0.9.0",
];

$patches = array_reverse($patches);

/* ======================================================
 * 🌍 PATH BASE
 * ====================================================== */
$host    = $isCli ? Config::$LinkUpdate : ($_SERVER['HTTP_HOST'] ?? 'localhost');
$baseUrl = "https://{$host}";

$baseDir    = realpath(__DIR__ . '/../');
$coreDir    = realpath($baseDir . '/Core');
$myFilesDir = realpath($baseDir . '/MyFiles');

if (!$coreDir || !$myFilesDir) {
  exit("❌ Directory Core o MyFiles non trovata\n");
}

/* ======================================================
 * ❌ POLICY DI ESCLUSIONE (CORE-SAFE)
 * ====================================================== */

/**
 * File che NON devono MAI essere aggiornati
 */
$DISALLOWED_FILES = [
  'Core/Classi/Menu.php',
  'Core/Classi/MenuManager.php',
  'ConfigFiles/config.local.json',
];

/**
 * Directory escluse dagli update core
 */
$DISALLOWED_DIRS = [
  'Core/cache',
  'App/Theme',
  'App/Plugins/Custom',
  'App/Controllers',
  'App/Models'
];

/* ======================================================
 * 🧠 CORE.JSON BASE
 * ====================================================== */
$coreJson = [
  "latest_core"      => $version,
  "security_patches" => $patches,
  "files"            => [],
  "zip"              => null
];

/* ======================================================
 * 📦 Aggiunge un file al core.json + MyFiles
 * ====================================================== */
function addFile(
  string $absolutePath,
  string $relativePath,
  array &$coreJson,
  string $myFilesDir,
  string $baseUrl
): void {

  $txtName = str_replace(['/', '\\'], '_', $relativePath) . '.txt';
  $txtPath = $myFilesDir . DIRECTORY_SEPARATOR . $txtName;

  file_put_contents($txtPath, file_get_contents($absolutePath));

  $coreJson['files'][] = [
    'url'    => $baseUrl . '/MyFiles/' . $txtName,
    'path'   => $relativePath,
    'sha256' => hash_file('sha256', $absolutePath)
  ];
}

/* ======================================================
 * 📂 SCANSIONE RICORSIVA CON ESCLUSIONI
 * ====================================================== */
function scanDirRecursive(
  string $dir,
  string $basePath,
  array &$coreJson,
  string $myFilesDir,
  string $baseUrl,
  array $disallowedFiles,
  array $disallowedDirs
): void {

  $it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
  );

  foreach ($it as $file) {

    if ($file->isDir()) {
      continue;
    }

    $absolutePath = $file->getPathname();

    // estensioni consentite
    $ext = strtolower($file->getExtension());
    if (!in_array($ext, ['php', 'html'], true)) {
      continue;
    }

    // escludi cache
    if (str_contains($absolutePath, DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR)) {
      continue;
    }

    $relative = ltrim(
      str_replace($basePath, '', $absolutePath),
      '/\\'
    );

    // ❌ directory escluse
    foreach ($disallowedDirs as $badDir) {
      if (str_starts_with($relative, rtrim($badDir, '/'))) {
        continue 2;
      }
    }

    // ❌ file esclusi
    if (in_array($relative, $disallowedFiles, true)) {
      continue;
    }

    addFile($absolutePath, $relative, $coreJson, $myFilesDir, $baseUrl);
  }
}

/* ======================================================
 * 1️⃣ CORE
 * ====================================================== */
scanDirRecursive(
  $coreDir,
  $baseDir,
  $coreJson,
  $myFilesDir,
  $baseUrl,
  $DISALLOWED_FILES,
  $DISALLOWED_DIRS
);

/* ======================================================
 * 2️⃣ FILE SINGOLI DI FRAMEWORK
 * ====================================================== */
$fixedFiles = [
  'install.php',
  'cli.php',
  'index.php',
  'ConfigFiles/bootstrap.php',
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
    scanDirRecursive(
      $full,
      $baseDir,
      $coreJson,
      $myFilesDir,
      $baseUrl,
      $DISALLOWED_FILES,
      $DISALLOWED_DIRS
    );
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
