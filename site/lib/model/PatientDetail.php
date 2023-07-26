<?php

namespace App\Model;

use App\Model\DataTransferObject;
use App\Model\Patient;
use App\Model\Contact;

class PatientDetail extends Patient
{
    use DataTransferObject;

    public string $nationalId;
    public string $tel;
    public string $mobile;
    public string $email;
    public string $address;
    public string $bloodType;
    public ?bool $married;
    public ?float $height;
    public ?float $weight;
    /**
     * @var Contact[]
     */
    public array $emergencyContacts;

    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->emergencyContacts = [];
        $this->nationalId = $data['nationalId'];
        $this->tel = $data['tel'];
        $this->mobile = $data['mobile'];
        $this->email = $data['email'];
        $this->address = $data['address'];
        $this->bloodType = $data['bloodType'];
        if (in_array($this->bloodType, ['A', 'B', 'AB', 'O']) === false) {
            $this->bloodType = 'unknown';
        }
        if ($data['married'] === true) {
            $this->married = true;
        } else if ($data['married'] === false) {
            $this->married = false;
        } else {
            $this->married = null;
        }
        $this->height = floatval($data['height']);
        if ($this->height < 2) {
            $this->height = null;
        }
        $this->weight = floatval($data['weight']);
        if ($this->weight < 2) {
            $this->weight = null;
        }
        if (is_array($data['emergencyContacts'])) {
            foreach ($data['emergencyContacts'] as $contact) {
                $this->emergencyContacts[] = new Contact($contact);
            }
        }
    }
}
