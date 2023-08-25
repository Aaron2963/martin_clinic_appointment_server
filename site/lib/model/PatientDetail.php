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
        $data['mobile'] = str_replace(['-', ' '], '', $data['mobile']);
        if (!preg_match('/^09\d{8}$/', $data['mobile'])) {
            throw new \Exception('Invalid mobile number, must be 09xxxxxxxx');
        }
        $this->mobile = $data['mobile'];
        $this->email = $data['email'];
        $this->address = $data['address'];
        $this->bloodType = $data['bloodType'];
        if (in_array($this->bloodType, ['A', 'B', 'AB', 'O']) === false) {
            $this->bloodType = 'unknown';
        }
        if ($data['married'] === true || $data['married'] === 'true' || $data['married'] === '1') {
            $this->married = true;
        } else if ($data['married'] === false || $data['married'] === 'false' || $data['married'] === '0') {
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
                $this->emergencyContacts[] = Contact::fromArray($contact);
            }
        }
    }

    public function toDBArray(array $fields = ['patientId', 'fullName', 'gender', 'birthday', 'nationalId', 'tel', 'mobile', 'mobile', 'email', 'address', 'bloodType', 'emergencyContacts']): array
    {
        $mapping = [
            'patientId' => 'UserID',
            'fullName' => 'FullName',
            'gender' => 'Gender',
            'birthday' => 'Birthday',
            'nationalId' => 'IDTNO',
            'tel' => 'TEL1',
            'mobile' => 'MobileTEL1',
            'email' => 'EML1',
            'address' => 'StreetADR',
            'bloodType' => 'BloodTYP',
        ];
        $data = [];
        foreach ($fields as $field) {
            if (isset($mapping[$field])) {
                $data[$mapping[$field]] = $this->$field;
            } else if ($field === 'emergencyContacts') {
                $data['ContactSet'] = json_encode($this->emergencyContacts);
            }
        }
        if (array_key_exists('Marriage', $data) && $this->married != null) {
            $data['Marriage'] = $this->married ? 1 : 0;
        }
        if (array_key_exists('Height', $data) && $this->height != null) {
            $data['Height'] = $this->height;
        }
        if (array_key_exists('Weight', $data) && $this->weight != null) {
            $data['Weight'] = $this->weight;
        }
        if (array_key_exists('MobileTEL1', $data)) {
            $data['LoginName'] = $data['MobileTEL1'];
        }
        return $data;
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        $data['emergencyContacts'] = array_map(function ($contact) {
            return $contact->toArray();
        }, $this->emergencyContacts);
        return $data;
    }
}
