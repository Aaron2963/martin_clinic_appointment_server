<?php

require_once __DIR__ . '/../site/vendor/dearsoft-com/dbsmod/src/utility_class/Utility.php';
require_once __DIR__ . '/../site/dev/lib/GithubRepo.php';

use MISA\App\GithubRepo;

// set file encoding
mb_internal_encoding("UTF-8");

// site dir path
$siteDir = __DIR__ . '/../site';

// github token
$token = $_ENV['GITHUB_PAT'];

// database schema
$databaseSchema = [
  [
    "hostingService" => "https://github.com",
    "repository" => "dearsoft-com/misa_database",
    "branch" => "develop",
    "path" => "/",
    "public" => false,
    "disabled" => false
  ],
  [
    "hostingService" => "https://github.com",
    "repository" => "dearsoft-com/customer_extension_database",
    "branch" => "eecp",
    "path" => "/sql",
    "public" => false,
    "disabled" => false
  ]
];
$sqlFiles = [];
$presetData = [];
foreach ($databaseSchema as $db) {
  if (isset($db['disabled']) && $db['disabled'] == true) {
    continue;
  }
  if (strtolower($db['hostingService']) == 'https://github.com') {
    $repoName = explode('/', $db['repository']);
    $project = $repoName[1];
    $repo = new GithubRepo($repoName[0], $repoName[1], $db['public'] ? '' : $token, $db['branch']);
    $repo->clone("/usr/local/$project");
  }
  if (strtolower($db['hostingService']) == 'local') {
    $project = $db['repository'];
    $deposit = '/usr/local/' . $db['repository'];
    mkdir($deposit);
    //copy files in $siteDir . $db['source'] to $deposit
    $files = glob($siteDir . '/' . $db['source'] . '/*');
    foreach ($files as $file) {
      copy($file, $deposit . '/' . basename($file));
    }
  }
  // composer install
  if (file_exists("/usr/local/$project/composer.json")) {
    $cmd = "cd /usr/local/$project && composer install";
    exec($cmd);
  }
  // get sql files
  $path = $db['path'] ?? '/';
  if (substr($path, -1) != '/') $path .= '/';
  $files = glob("/usr/local/$project$path*.sql");
  if ($files !== false) {
    $sqlFiles = array_merge($sqlFiles, $files);
  }
  // get preset data
  $files = glob("/usr/local/$project/preset-data/*.sql");
  if ($files !== false) {
    $presetData = array_merge($presetData, $files);
  }
}

$conn = new PDO("mysql:host=db;dbname=misa_database", 'misa', 'misa');
$conn->exec('SET CHARACTER SET utf8;');
$conn->exec("SET NAMES 'utf8'");
$conn->exec("SET CHARACTER_SET_CLIENT=utf8");
$conn->exec("SET CHARACTER_SET_RESULTS=utf8");
$conn->exec('SET GLOBAL sql_mode = "NO_ENGINE_SUBSTITUTION";');
$conn->exec('USE misa_database;');

// import sql files
$importedSqlFilesCount = 0;
foreach ($sqlFiles as $file) {
  $sql = file_get_contents($file);
  $result = $conn->exec($sql);
  if ($result === false) {
    echo "[ERROR] SQL FILE $file IMPORT FAILED" . PHP_EOL;
    continue;
  }
  $importedSqlFilesCount++;
}
echo "IMPORTED $importedSqlFilesCount SQL FILES" . PHP_EOL;

// import preset data
foreach ($presetData as $file) {
  $sql = file_get_contents($file);
  $conn->exec($sql);
  echo "PRESET DATA $file IMPORTED" . PHP_EOL;
}
