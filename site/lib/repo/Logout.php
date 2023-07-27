<?php

namespace App\Repo;

use Lin\AppPhp\Server\App;
use Lin\AppPhp\Server\RestfulApp;
use MISA\DBSMOD\DBSMOD_OAuthToken;

class Logout extends RestfulApp
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
            $ResponseBody = $this->Psr17Factory->createStream(json_encode([
                'message' => $th->getMessage()
            ]));
            return $this->Psr17Factory->createResponse(401)->withBody($ResponseBody)->withHeader('Content-Type', 'application/json');
        }
    }
}
