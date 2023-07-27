<?php

namespace App\Repo;

use Lin\AppPhp\Authorization\AuthorizationInterface;
use MISA\DBSMOD\DBSMOD_OAuthToken;

class Authorization implements AuthorizationInterface
{
    public function Authorize($Token, $ResourceScopes = [])
    {
        global $Link, $DB_TABLE, $JWT_PUBLIC;
        $Mod = new DBSMOD_OAuthToken($Link, $DB_TABLE);
        return $Mod->CheckToken($Token, $JWT_PUBLIC);
    }
}