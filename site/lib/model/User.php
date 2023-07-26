<?php

namespace App\Model;

use App\Model\DataTransferObject;

class User
{
    use DataTransferObject;

    public string $userId;
    public string $loginName;
    public string $fullName;

    public function __construct(array $data)
    {
        $this->userId = $data['userId'];
        $this->loginName = $data['loginName'];
        $this->fullName = $data['fullName'];
    }
}
