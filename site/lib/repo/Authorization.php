<?php

namespace App\Repo;

use Lin\AppPhp\Authorization\OAuthAuthorization;

class Authorization extends OAuthAuthorization
{
    public function __construct()
    {
        global $JWT_PUBLIC;
        parent::__construct($JWT_PUBLIC);
    }

    protected function IsTokenRevoked($JTI)
    {
        return false;
    }
}
