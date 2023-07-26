<?php

namespace App\Model;

use App\Model\DataTransferObject;

class Physician
{
    use DataTransferObject;

    public string $physicianId;
    public string $fullName;

    public function __construct(array $data)
    {
        $this->physicianId = $data['physicianId'];
        $this->fullName = $data['fullName'];
    }
}
