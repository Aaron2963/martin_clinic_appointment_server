<?php

namespace App;

require_once __DIR__ . "/../settings/config.inc.php";
require_once __DIR__ . "/../lib/model/loader.php";
require_once __DIR__ . "/../lib/repo/Authorization.php";
require_once __DIR__ . "/../lib/repo/AppointmentRepo.php";
require_once __DIR__ . "/../lib/repo/ClinicRepo.php";
require_once __DIR__ . "/../lib/repo/LoginRepo.php";
require_once __DIR__ . "/../lib/repo/LogoutRepo.php";
require_once __DIR__ . "/../lib/repo/PatientRepo.php";
require_once __DIR__ . "/../lib/repo/PhysicianRepo.php";

use App\Repo\Authorization;
use Lin\AppPhp\Server\App;
use App\Repo\AppointmentRepo;
use App\Repo\ClinicRepo;
use App\Repo\LoginRepo;
use App\Repo\LogoutRepo;
use App\Repo\PatientRepo;
use App\Repo\PhysicianRepo;

// get resource and args from uri
$uri = $_SERVER['REQUEST_URI'];
$uri = explode('?', $uri)[0];
$uri = explode('#', $uri)[0];
$uri = trim($uri, '/');
$uri = explode('/', $uri);
$resource = $uri[0];
$URI_ARGUMENTS = [];
for ($i = 0; $i < count($uri); $i++) {
    if ($uri[$i] === 'api') {
        $resource = $uri[$i + 1];
        $URI_ARGUMENTS = array_slice($uri, $i + 2);
        break;
    }
}

// handle preflight request: allow CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: GET, PUT, PATCH, POST, DELETE, OPTIONS");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Origin, Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
    exit;
}

$Request = App::CreateServerRequest();
if (count($URI_ARGUMENTS) > 0) {
    $OriParams = $Request->getQueryParams();
    $Request = $Request->withQueryParams(array_merge($OriParams, ['_id' => $URI_ARGUMENTS[0]]));
}

switch ($resource) {
    case 'appointment':
        $App = new AppointmentRepo();
        break;
    case 'clinic':
        $App = new ClinicRepo();
        break;
    case 'login':
        $App = new LoginRepo();
        break;
    case 'logout':
        $App = new LogoutRepo();
        break;
    case 'patient':
        $App = new PatientRepo();
        break;
    case 'physician':
        $App = new PhysicianRepo();
        break;
    default:
        http_response_code(404);
        exit;
}

$App->WithAuthorization(new Authorization());
$App->HandleRequest($Request);
$App->AddHeaders(['Access-Control-Allow-Origin' => '*']);
$App->SendResponse();
exit;
