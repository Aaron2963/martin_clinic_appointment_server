<?php

namespace App;

require_once __DIR__ . "/../settings/config.inc.php";
require_once __DIR__ . "/../lib/model/loader.php";
require_once __DIR__ . "/../lib/repo/PatientRepo.php";
require_once __DIR__ . "/../lib/repo/Authorization.php";

use App\Repo\Authorization;
use Lin\AppPhp\Server\App;
use App\Repo\PatientRepo;

$Request = App::CreateServerRequest();
if (count($URI_ARGUMENTS) > 0) {
    $OriParams = $Request->getQueryParams();
    $Request = $Request->withQueryParams(array_merge($OriParams, ['_id' => $URI_ARGUMENTS[0]]));
}

$App = new PatientRepo();
$App->WithAuthorization(new Authorization());
$App->HandleRequest($Request);
$App->SendResponse();
exit;
