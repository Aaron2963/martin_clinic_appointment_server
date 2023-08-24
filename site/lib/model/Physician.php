<?php

namespace App\Model;

use App\Model\DataTransferObject;

class Physician
{
    use DataTransferObject;

    public string $physicianId;
    public string $fullName;
    static public string $groupId = '0s0v8xveildc7n6d';

    public function __construct(array $data)
    {
        $this->physicianId = $data['physicianId'];
        $this->fullName = $data['fullName'];
    }
}
