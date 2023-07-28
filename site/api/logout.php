<?php

namespace App;

require_once __DIR__ . "/../settings/config.inc.php";
require_once __DIR__ . "/../lib/model/loader.php";
require_once __DIR__ . "/../lib/repo/LogoutRepo.php";
require_once __DIR__ . "/../lib/repo/Authorization.php";

use App\Repo\Authorization;
use Lin\AppPhp\Server\App;
use App\Repo\LogoutRepo;

$App = new LogoutRepo();
$App->WithAuthorization(new Authorization());
$App->HandleRequest(App::CreateServerRequest());
$App->SendResponse();
exit;
