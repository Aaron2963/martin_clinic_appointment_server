<?php

namespace App\Model;

use App\Model\DataTransferObject;

class Department
{
    public string $departmentId;
    public string $name;

    public function __construct(array $data)
    {
        $this->departmentId = $data['departmentId'];
        $this->name = $data['name'];
    }
}
