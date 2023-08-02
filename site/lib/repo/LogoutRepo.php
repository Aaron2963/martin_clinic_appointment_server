<?php

namespace App\Repo;

use Lin\AppPhp\Server\App;
use Lin\AppPhp\Server\RestfulApp;
use MISA\DBSMOD\DBSMOD_OAuthToken;

class LogoutRepo extends RestfulApp
{
    public function OnPost()
    {
        global $Link, $DB_TABLE, $JWT_PUBLIC;
        try {
            $AuthResult = $this->AuthorizeRequest();
            if ($AuthResult === false) {
                throw new \Exception('Token is invalid');
            }
            $Mod = new DBSMOD_OAuthToken($Link, $DB_TABLE);
            $Token = explode(' ', $this->GetServerRequest()->getHeader('Authorization')[0])[1];
            $Mod->DeleteToken($Token, $JWT_PUBLIC);
            return App::NoContentResponse();
        } catch (\Throwable $th) {
            return App::JsonResponse(['message' => $th->getMessage()], 401);
        }
    }
}
