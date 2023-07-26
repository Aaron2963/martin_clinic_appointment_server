<?php

namespace App\Model;

use App\Model\DataTransferObject;
use App\Model\Department;

class Clinic
{
    use DataTransferObject;

    public string $clinicId;
    public string $name;
    /**
     * @var Department[]
     */
    public array $departments;

    public function __construct(array $data)
    {
        $this->clinicId = $data['clinicId'];
        $this->name = $data['name'];
        $this->departments = [];
        if (is_array($data['departments'])) {
            foreach ($data['departments'] as $department) {
                $this->departments[] = new Department($department);
            }
        }
    }
}