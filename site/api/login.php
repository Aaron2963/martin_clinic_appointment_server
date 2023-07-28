<?php

namespace App;

require_once __DIR__ . "/../settings/config.inc.php";
require_once __DIR__ . "/../lib/model/loader.php";
require_once __DIR__ . "/../lib/repo/LoginRepo.php";

use Lin\AppPhp\Server\App;
use App\Repo\LoginRepo;

$App = new LoginRepo();
$App->HandleRequest(App::CreateServerRequest());
$App->SendResponse();
exit;
