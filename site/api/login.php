<?php

namespace App;

require_once __DIR__ . "/../settings/config.inc.php";
require_once __DIR__ . "/../lib/model/loader.php";
require_once __DIR__ . "/../lib/repo/Login.php";

use Lin\AppPhp\Server\App;
use App\Repo\Login;

$App = new Login();
$App->HandleRequest(App::CreateServerRequest());
$App->SendResponse();
exit;
