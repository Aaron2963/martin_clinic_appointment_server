<?php

use App\Repo\AppointmentRepo;

require_once __DIR__ . "/../settings/config.inc.php";
require_once __DIR__ . "/../lib/model/loader.php";
require_once __DIR__ . "/../lib/repo/AppointmentRepo.php";

$repo = new AppointmentRepo();
// $result = $repo->QueryList([
//     'offset' => 0,
//     'limit' => 10
// ]);

$result = $repo->QueryDetail('1');

var_dump($result);
print_r($result);