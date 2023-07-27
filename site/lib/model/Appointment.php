<?php

namespace App\Model;

use App\Model\DataTransferObject;
use DateTime;

class Appointment
{
    use DataTransferObject;

    public string $appointmentId;
    public Patient $patient;
    public Physician $physician;
    public Clinic $clinic;
    public Department $department;
    public string $status;
    public string $serialNoInTimeShift;
    public string $note;
    public string $date;
    public string $shift;
    public string $createAt;
    public ?string $confirmeAt;
    public ?string $cancelAt;

    public function __construct(array $array)
    {
        $this->appointmentId = $array['appointmentId'];
        $this->patient = Patient::fromArray($array['patient']);
        $this->physician = Physician::fromArray($array['physician']);
        $this->clinic = Clinic::fromArray($array['clinic']);
        $this->department = Department::fromArray($array['department']);
        $this->serialNoInTimeShift = $array['serialNoInTimeShift'];
        $this->note = $array['note'];
        if (in_array($array['status'], ['pending', 'confirmed', 'cancelled', 'processing', 'completed'])) {
            $this->status = $array['status'];
        } else {
            throw new \Exception("Invalid appiontment status: {$array['status']}");
        }
        if (preg_match('/^T[0-2][0-9]00$/', $array['shift'])) {
            $h = intval(substr($array['shift'], 1, 2));
            if ($h < 8 || $h > 21) {
                throw new \Exception("Invalid appiontment shift: {$array['shift']}");
            }
            $this->shift = $array['shift'];
        } else {
            throw new \Exception("Invalid appiontment shift: {$array['shift']}");
        }
        $this->date = new DateTime($array['date']);
        if ($this->date === false) {
            throw new \Exception("Invalid appiontment date: {$array['date']}");
        }
        $this->createAt = new DateTime($array['createAt']);
        if ($this->createAt === false) {
            throw new \Exception("Invalid appiontment createAt: {$array['createAt']}");
        }
        $this->confirmeAt = new DateTime($array['confirmeAt']);
        if ($this->confirmeAt === false) {
            $this->confirmeAt = null;
        }
        $this->cancelAt = new DateTime($array['cancelAt']);
        if ($this->cancelAt === false) {
            $this->cancelAt = null;
        }
    }
}
