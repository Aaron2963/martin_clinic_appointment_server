<?php

namespace App\Model;

use App\Model\DataTransferObject;

class User
{
    use DataTransferObject;

    public string $userId;
    public string $loginName;
    public string $fullName;
    static public string $groupId = '0s0wsm4a1gmmwe03';

    public function __construct(array $data)
    {
        $this->userId = $data['userId'];
        $this->loginName = $data['loginName'];
        $this->fullName = $data['fullName'];
    }
}
