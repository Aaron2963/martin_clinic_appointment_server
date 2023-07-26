<?php

namespace App\Model;

use App\Model\DataTransferObject;

class Contact
{
    use DataTransferObject;

    public string $fullName;
    public string $tel;
    public string $relationship;

    public function __construct(array $data)
    {
        $this->fullName = $data['fullName'];
        $this->relationship = $data['relationship'];
        $this->tel = $data['tel'];
    }
}
