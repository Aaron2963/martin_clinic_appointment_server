<?php

namespace App\Model;

$files = ['DataTransferObject', 'Department', 'Clinic', 'Contact', 'Patient', 
    'PatientDetail', 'User', 'Physician', 'Appointment'];

foreach ($files as $file) {
    require_once __DIR__ . "/$file.php";
}
