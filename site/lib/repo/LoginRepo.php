<?php

namespace App\Repo;

use Lin\AppPhp\Server\App;
use Lin\AppPhp\Server\RestfulApp;
use MISA\DBSMOD\DBSMOD_OAuthToken;
use MISA\DBSMOD\DBSMOD_User;
use App\Model\User;

class LoginRepo extends RestfulApp
{
    public function OnPost()
    {
        global $Link, $DB_TABLE, $JWT_SECRET;
        $Data = $this->GetServerRequest()->getParsedBody();
        try {
            $LoginName = $Data['loginName'];
            $Password = $Data['password'];
            if (empty($LoginName) || empty($Password)) {
                throw new \Exception('Login name or password is empty');
            }
            $ModUser = new DBSMOD_User($Link, $DB_TABLE);
            $Result = $ModUser->Login($LoginName, $Password);
            if ($Result === false) {
                throw new \Exception('Login name or password is incorrect');
            }
            $User = $ModUser->Select($Result['UserID'], ['UserID','FullName','LoginName']);
            $Mod = new DBSMOD_OAuthToken($Link, $DB_TABLE);
            $Mod->ExpireTime = 60 * 60 * 24 * 30;
            $Token = $Mod->CreateToken($User['UserID'], $JWT_SECRET);
            $Jwt = $Token->ToString($JWT_SECRET);
            if ($Jwt === false) {
                throw new \Exception('Create token failed: ' . $Token->Error->getMessage());
            }
            $UserObj = User::fromArray($User);
            return App::JsonResponse([
                'token' => $Jwt,
                'user' => $UserObj->toArray(),
            ]);
        } catch (\Throwable $th) {
            return App::JsonResponse(['message' => $th->getMessage()], 401);
        }
    }

    public function OnGet()
    {
        global $Link, $DB_TABLE, $JWT_PUBLIC;
        try {
            $Mod = new DBSMOD_OAuthToken($Link, $DB_TABLE);
            $ModUser = new DBSMOD_User($Link, $DB_TABLE);
            $Token = explode(' ', $this->GetServerRequest()->getHeader('Authorization')[0])[1];
            $Result = $Mod->CheckToken($Token, $JWT_PUBLIC);
            if ($Result === false) {
                throw new \Exception('Token is invalid');
            }
            $User = $ModUser->Select($Result->GetSUB(), ['UserID','FullName','LoginName']);
            $UserObj = User::fromArray($User);
            return App::JsonResponse(['user' => $UserObj->toArray()]);
        } catch (\Throwable $th) {
            return App::JsonResponse(['message' => $th->getMessage()], 401);
        }
    }
}
