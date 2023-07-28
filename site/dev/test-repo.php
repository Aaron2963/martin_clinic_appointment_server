<?php

use App\Repo\PatientRepo;

require_once __DIR__ . "/../settings/config.inc.php";
require_once __DIR__ . "/../lib/model/loader.php";
require_once __DIR__ . "/../lib/repo/PatientRepo.php";

$repo = new PatientRepo();
$result = $repo->QueryList([
    'offset' => 0,
    'limit' => 10
]);
$result = $repo->QueryDetail('0r0lpk3sqjcq6e40');

var_dump($result);
