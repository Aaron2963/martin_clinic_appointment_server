<?php

namespace App\Model;

use App\Model\DataTransferObject;
use DateTime;

class Patient
{
    use DataTransferObject;

    public string $patientId;
    public string $fullName;
    public string $gender;
    public ?string $birthday;
    static public string $groupId = '0s0v8xvcclg2o1ra';

    public function __construct(array $data)
    {
        $this->patientId = $data['patientId'] ?? $data['userId'];
        $this->fullName = $data['fullName'];
        if (in_array($data['gender'], ['male', 'female'])) {
            $this->gender = $data['gender'];
        } else {
            $this->gender = 'other';
        }
        try {
            $date = new DateTime($data['birthday']);
            if ($date === false) {
                $this->birthday = null;
            } else {
                $this->birthday = $date->format('Y-m-d');
            }
        } catch (\Throwable $th) {
            $this->birthday = null;
        }
    }
}
