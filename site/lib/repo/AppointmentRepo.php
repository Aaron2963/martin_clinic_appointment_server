<?php

namespace App\Repo;

use App\Model\Appointment;
use Lin\AppPhp\Server\App;
use Lin\AppPhp\Server\RestfulApp;
use MISA\DBSMOD\DBSAction;
use MISA\DBSMOD\DBSMOD_Appointment;
use MISA\DBSMOD\DBSMOD_User;
use MISA\DBSMOD\Utility;

class AppointmentRepo extends RestfulApp
{
    public function OnGet()
    {
        $Data = $this->GetServerRequest()->getQueryParams();
        $AuthResult = $this->AuthorizeRequest();
        if (!$AuthResult) {
            return App::UnauthorizedResponse();
        }
        if (empty($Data['_id'])) {
            $List = $this->QueryList($Data);
            $ResponseBody = $this->Psr17Factory->createStream(json_encode($List));
            return $this->Psr17Factory->createResponse(200)->withBody($ResponseBody)
                ->withHeader('Content-Type', 'application/json');
        } else {
            $Appt = $this->QueryDetail($Data['_id']);
            if ($Appt === null) {
                return $this->Psr17Factory->createResponse(404);
            }
            $ResponseBody = $this->Psr17Factory->createStream(json_encode($Appt));
            return $this->Psr17Factory->createResponse(200)->withBody($ResponseBody)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    public function OnPost()
    {
        global $Link, $DB_TABLE, $Appointment_Tb, $NowDateTime;
        $Data = $this->GetServerRequest()->getParsedBody();
        $AuthResult = $this->AuthorizeRequest();
        if (!$AuthResult) {
            return App::UnauthorizedResponse();
        }
        try {
            // Validate Required Fields
            $RequiredFields = ['patientId', 'date', 'shift', 'clinicId', 'departmentId'];
            $InvalidFields = [];
            foreach ($RequiredFields as $Field) {
                if (empty($Data[$Field])) {
                    $InvalidFields[] = $Field;
                }
            }
            if (count($InvalidFields) > 0) {
                throw new \Exception('Invalid Fields: ' . implode(',', $InvalidFields));
            }
            // Purity Data
            $Data = Utility::SafeScript($Data);
            $Data = Utility::SafeSQL($Data, $Link);
            // Validate Date
            $Date = date('Y-m-d', strtotime($Data['date']));
            if (!preg_match('/^T[0-2]\d00/', $Data['shift'])) {
                throw new \Exception('Invalid Shift: ' . $Data['shift']);
            }
            // Fetch Patient Info
            $UserMod = new DBSMOD_User($Link, $DB_TABLE);
            $Patient = $UserMod->Select($Data['patientId'], ['FullName', 'TEL1', 'TEL1EXT', 'MobileTEL1', 'EML1']);
            if ($Patient === false) {
                throw new \Exception('Can not find patient' . $UserMod->Error->getMessage());
            }
            // Fetch Last Serial No in shift
            $Act = new DBSAction($Link, $DB_TABLE, $Appointment_Tb);
            $Act->AddSelect(['SERNOInTimeShift', 'SEQInTimeShift']);
            $Act->AddCondition('DPTID = :departmentId', ['departmentId' => $Data['departmentId']], 'and');
            $Act->AddCondition('StartDateTime = :date', ['date' => $Date], 'and');
            $Act->AddCondition('TimeShift = :shift', ['shift' => $Data['shift']], 'and');
            $Act->AddOrder('SERNOInTimeShift', 'DESC');
            $Act->AddLimit(0, 1);
            $Act->RenderSQL('_SELECT');
            $Rows = $Act->ExecuteSQL();
            if ($Act->Error != null) {
                throw $Act->Error;
            }
            $SerialNoInTimeShift = 1;
            if (count($Rows) > 0) {
                $SerialNoInTimeShift = $Rows[0]['SERNOInTimeShift'] + 1;
            }
            // Create Appointment
            $Appt = [
                'CUSID' => $Data['clinicId'],
                'DPTID' => $Data['departmentId'],
                'BookerID' => $Data['patientId'],
                'StartDateTime' => $Date,
                'TimeShift' => $Data['shift'],
                'ApplyDateTime' => $NowDateTime,
                'BookerName' => $Patient['FullName'],
                'BookerTEL' => $Patient['TEL1'],
                'BookerTELEXT' => $Patient['TEL1EXT'],
                'BookerMobileTEL' => $Patient['MobileTEL1'],
                'BookerEmail' => $Patient['EML1'],
                'SERNOInTimeShift' => $SerialNoInTimeShift,
                'SEQInTimeShift' => $SerialNoInTimeShift,
            ];
            if (!empty($Data['note'])) {
                $Appt['ApplyNote'] = $Data['note'];
            }
            if (!empty($Data['physicianId'])) {
                $Physician = $UserMod->Select($Data['physicianId'], ['FullName']);
                $Appt['AppointerID'] = $Data['physicianId'];
                $Appt['AppointerName'] = $Physician['FullName'];
            }
            $ApptMod = new DBSMOD_Appointment($Link, $DB_TABLE);
            $CreateResult = $ApptMod->Create($Appt);
            if ($CreateResult === false) {
                throw new \Exception('Create Appointment Failed: ' . $ApptMod->Error->getMessage());
            }
            if (!empty($Data['status'])) {
                $this->UpdateStatus($CreateResult['AppointmentID'], $Data['status']);
            }
            return App::NoContentResponse();
        } catch (\Throwable $th) {
            $ResponseBody = $this->Psr17Factory->createStream(json_encode([
                'message' => $th->getMessage(),
            ]));
            return $this->Psr17Factory->createResponse(400)->withBody($ResponseBody)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    public function OnPut()
    {
        global $Link, $DB_TABLE, $Appointment_Tb, $NowDateTime;
        $Data = $this->GetServerRequest()->getParsedBody();
        $AuthResult = $this->AuthorizeRequest();
        if (!$AuthResult) {
            return App::UnauthorizedResponse();
        }
        $ApptId = $this->GetServerRequest()->getQueryParams()['_id'] ?? null;
        if (empty($ApptId)) {
            return $this->Psr17Factory->createResponse(404);
        }
        try {
            // Purity Data
            $Data = Utility::SafeScript($Data);
            $Data = Utility::SafeSQL($Data, $Link);
            $Appt = [];
            // Validate Date
            if (isset($Data['date'])) {
                $Date = date('Y-m-d', strtotime($Data['date']));
                $Appt['StartDateTime'] = $Date;
            }
            if (isset($Data['shift'])) {
                if (!preg_match('/^T[0-2]\d00/', $Data['shift'])) {
                    throw new \Exception('Invalid Shift: ' . $Data['shift']);
                }
                $Appt['TimeShift'] = $Data['shift'];
            }
            // Fetch Patient Info
            if (isset($Data['patientId'])) {
                $UserMod = new DBSMOD_User($Link, $DB_TABLE);
                $Patient = $UserMod->Select($Data['patientId'], ['FullName', 'TEL1', 'TEL1EXT', 'MobileTEL1', 'EML1']);
                if ($Patient === false) {
                    throw new \Exception('Can not find patient' . $UserMod->Error->getMessage());
                }
                $Appt['BookerID'] = $Data['patientId'];
                $Appt['BookerName'] = $Patient['FullName'];
                $Appt['BookerTEL'] = $Patient['TEL1'];
                $Appt['BookerTELEXT'] = $Patient['TEL1EXT'];
                $Appt['BookerMobileTEL'] = $Patient['MobileTEL1'];
                $Appt['BookerEmail'] = $Patient['EML1'];
            }
            if (isset($Data['clinicId'])) {
                $Appt['CUSID'] = $Data['clinicId'];
            }
            if (isset($Data['departmentId'])) {
                $Appt['DPTID'] = $Data['departmentId'];
            }
            if (isset($Data['physicianId'])) {
                $Physician = $UserMod->Select($Data['physicianId'], ['FullName']);
                $Appt['AppointerID'] = $Data['physicianId'];
                $Appt['AppointerName'] = $Physician['FullName'];
            }
            if (isset($Data['note'])) {
                $Appt['ApplyNote'] = $Data['note'];
            }
            // Update Appointment
            $ApptMod = new DBSMOD_Appointment($Link, $DB_TABLE);
            $CreateResult = $ApptMod->Update($ApptId, $Appt);
            if ($CreateResult === false) {
                throw new \Exception('Update Appointment Failed: ' . $ApptMod->Error->getMessage());
            }
            if (!empty($Data['status'])) {
                $this->UpdateStatus($CreateResult['AppointmentID'], $Data['status']);
            }
            return App::NoContentResponse();
        } catch (\Throwable $th) {
            $ResponseBody = $this->Psr17Factory->createStream(json_encode([
                'message' => $th->getMessage(),
            ]));
            return $this->Psr17Factory->createResponse(400)->withBody($ResponseBody)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    public function QueryList($Data): array
    {
        global $Link, $DB_TABLE, $Appointment_Tb, $User_Tb, $CUSIFO_Tb, $DPT_Tb;
        $Act = new DBSAction($Link, $DB_TABLE, $Appointment_Tb);
        $Act->AddSelect([
            'appointmentId' => 'AppointmentID',
            'patientId' => 'BookerID',
            'patientName' => 'BookerName',
            'patientGender' => "(SELECT $User_Tb.Gender FROM $User_Tb WHERE $User_Tb.UserID = $Appointment_Tb.BookerID LIMIT 1)",
            'patientBirthday' => "(SELECT $User_Tb.Birthday FROM $User_Tb WHERE $User_Tb.UserID = $Appointment_Tb.BookerID LIMIT 1)",
            'physicianId' => 'AppointerID',
            'physicianName' => 'AppointerName',
            'clinicId' => 'CUSID',
            'clinicName' => "(SELECT $CUSIFO_Tb.Name FROM $CUSIFO_Tb WHERE $CUSIFO_Tb.CUSIFOID = $Appointment_Tb.CUSID LIMIT 1)",
            'departmentId' => 'DPTID',
            'departmentName' => "(SELECT $DPT_Tb.Name FROM $DPT_Tb WHERE $DPT_Tb.DPTID = $Appointment_Tb.DPTID LIMIT 1)",
            'status' => $this->GetStatusFlagExpression(),
            'serialNoInTimeShift' => 'SERNoInTimeShift',
            'note' => 'ApplyNote',
            'date' => 'StartDateTime',
            'shift' => 'TimeShift',
            'createAt' => 'ApplyDateTime',
            'confirmAt' => 'ArriveDateTime',
            'cancelAt' => 'CancelDateTime',
        ]);
        $Act->AddLimit($Data['offset'] ?? 0, $Data['limit'] ?? 10);
        if (isset($Data['patientId'])) {
            $Act->AddCondition("BookerID = '{$Data['patientId']}'", 'and');
        }
        if (isset($Data['patientName'])) {
            $Act->AddCondition("BookerName LIKE '%{$Data['patientName']}%'", 'and');
        }
        if (isset($Data['physicianId'])) {
            $Act->AddCondition("AppointerID = '{$Data['physicianId']}'", 'and');
        }
        if (isset($Data['clinicId'])) {
            $Act->AddCondition("CUSID = '{$Data['clinicId']}'", 'and');
        }
        if (isset($Data['departmentId'])) {
            $Act->AddCondition("DPTID = '{$Data['departmentId']}'", 'and');
        }
        if (isset($Data['status'])) {
            $Act->AddCondition($this->GetStatusFlagExpression() . " = '{$Data['status']}'", 'and');
        }
        if (isset($Data['startAt'])) {
            $Act->AddCondition("StartDateTime >= {$Data['startAt']}", 'and');
        }
        if (isset($Data['endAt'])) {
            $Act->AddCondition("StartDateTime <= {$Data['endAt']}", 'and');
        }
        if (isset($Data['shift'])) {
            $Act->AddCondition("TimeShift = '{$Data['shift']}'", 'and');
        }
        $Act->AddOrder('StartDateTime', 'DESC');
        $Act->RenderSQL('_SELECT');
        $Count = $Act->Count();
        $Rows = $Act->ExecuteSQL();
        if ($Act->Error != null) {
            throw $Act->Error;
        }
        $Appts = [];
        foreach ($Rows as $Row) {
            $Row['patient'] = [
                'UserID' => $Row['patientId'],
                'FullName' => $Row['patientName'],
                'Gender' => $Row['patientGender'],
                'Birthday' => $Row['patientBirthday'],
            ];
            $Row['physician'] = [
                'physicianId' => $Row['physicianId'],
                'fullName' => $Row['physicianName'],
            ];
            $Row['clinic'] = [
                'clinicId' => $Row['clinicId'],
                'name' => $Row['clinicName'],
            ];
            $Row['department'] = [
                'departmentId' => $Row['departmentId'],
                'name' => $Row['departmentName'],
            ];
            $Appt = Appointment::fromArray($Row);
            $Appts[] = $Appt->toArray();
        }
        return [
            'total' => (int) $Count,
            'data' => $Appts,
        ];
    }

    public function QueryDetail($ID): ?array
    {
        global $Link, $DB_TABLE, $Appointment_Tb, $User_Tb, $CUSIFO_Tb, $DPT_Tb;
        $Act = new DBSAction($Link, $DB_TABLE, $Appointment_Tb);
        $Act->AddSelect([
            'appointmentId' => 'AppointmentID',
            'patientId' => 'BookerID',
            'patientName' => 'BookerName',
            'patientGender' => "(SELECT $User_Tb.Gender FROM $User_Tb WHERE $User_Tb.UserID = $Appointment_Tb.BookerID LIMIT 1)",
            'patientBirthday' => "(SELECT $User_Tb.Birthday FROM $User_Tb WHERE $User_Tb.UserID = $Appointment_Tb.BookerID LIMIT 1)",
            'physicianId' => 'AppointerID',
            'physicianName' => 'AppointerName',
            'clinicId' => 'CUSID',
            'clinicName' => "(SELECT $CUSIFO_Tb.Name FROM $CUSIFO_Tb WHERE $CUSIFO_Tb.CUSIFOID = $Appointment_Tb.CUSID LIMIT 1)",
            'departmentId' => 'DPTID',
            'departmentName' => "(SELECT $DPT_Tb.Name FROM $DPT_Tb WHERE $DPT_Tb.DPTID = $Appointment_Tb.DPTID LIMIT 1)",
            'status' => $this->GetStatusFlagExpression(),
            'serialNoInTimeShift' => 'SERNoInTimeShift',
            'note' => 'ApplyNote',
            'date' => 'StartDateTime',
            'shift' => 'TimeShift',
            'createAt' => 'ApplyDateTime',
            'confirmAt' => 'ArriveDateTime',
            'cancelAt' => 'CancelDateTime',
        ]);
        $Act->AddPreCondition("AppointmentID = :id", ['id' => $ID]);
        $Act->RenderSQL('_SELECT');
        $Rows = $Act->ExecuteSQL();
        if ($Act->Error != null) {
            throw $Act->Error;
        }
        if (count($Rows) === 0) {
            return null;
        }
        $Row = $Rows[0];
        $Row['patient'] = [
            'UserID' => $Row['patientId'],
            'FullName' => $Row['patientName'],
            'Gender' => $Row['patientGender'],
            'Birthday' => $Row['patientBirthday'],
        ];
        $Row['physician'] = [
            'physicianId' => $Row['physicianId'],
            'fullName' => $Row['physicianName'],
        ];
        $Row['clinic'] = [
            'clinicId' => $Row['clinicId'],
            'name' => $Row['clinicName'],
        ];
        $Row['department'] = [
            'departmentId' => $Row['departmentId'],
            'name' => $Row['departmentName'],
        ];
        $Appt = Appointment::fromArray($Row);
        return $Appt->toArray();
    }

    protected function GetStatusFlagExpression(): string
    {
        $CancelledFlag = 'SUBSTR(StatusFlag, LENGTH(StatusFlag) - 1, 1)';
        $ServiceFlag = 'SUBSTR(StatusFlag, LENGTH(StatusFlag) - 3, 1)';
        $ConfirmedFlag = 'SUBSTR(StatusFlag, LENGTH(StatusFlag) - 2, 1)';
        return "IF($CancelledFlag = 'C', 'cancelled', IF($ServiceFlag = 'C', 'completed', IF($ServiceFlag = 'W', 'processing', IF($ConfirmedFlag = 'C', 'confirmed', 'pending'))))";
    }

    public function UpdateStatus(string $ApptID, string $Status)
    {
        global $Link, $DB_TABLE, $NowDateTime;
        $ApptMod = new DBSMOD_Appointment($Link, $DB_TABLE);
        $Appt = $ApptMod->Select($ApptID, ['StatusFlag']);
        $Data = ['StatusFlag' => $Appt['StatusFlag']];
        switch ($Status) {
            case 'confirmed':
                $Data['StatusFlag'] = $ApptMod->WriteFlag($Data['StatusFlag'], 0, 'C');
                $Data['StatusFlag'] = $ApptMod->WriteFlag($Data['StatusFlag'], 1, '-');
                $Data['StatusFlag'] = $ApptMod->WriteFlag($Data['StatusFlag'], 2, 'C');
                $Data['StatusFlag'] = $ApptMod->WriteFlag($Data['StatusFlag'], 3, '-');
                $Data['ArriveDateTime'] = $NowDateTime;
                break;
            case 'completed':
                $Data['StatusFlag'] = $ApptMod->WriteFlag($Data['StatusFlag'], 0, 'C');
                $Data['StatusFlag'] = $ApptMod->WriteFlag($Data['StatusFlag'], 1, '-');
                $Data['StatusFlag'] = $ApptMod->WriteFlag($Data['StatusFlag'], 2, 'C');
                $Data['StatusFlag'] = $ApptMod->WriteFlag($Data['StatusFlag'], 3, 'C');
                $Data['CompleteDateTime'] = $NowDateTime;
                break;
            case 'cancelled':
                $Data['StatusFlag'] = $ApptMod->WriteFlag($Data['StatusFlag'], 1, 'C');
                $Data['CancelDateTime'] = $NowDateTime;
                break;
            case 'processing':
                $Data['StatusFlag'] = $ApptMod->WriteFlag($Data['StatusFlag'], 1, '-');
                $Data['StatusFlag'] = $ApptMod->WriteFlag($Data['StatusFlag'], 3, 'W');
                break;
        }
        $UpdateResult = $ApptMod->Update($ApptID, $Data);
        if ($UpdateResult === false) {
            throw new \Exception('Update Appointment Status Failed: ' . $ApptMod->Error->getMessage());
        }
    }
}
